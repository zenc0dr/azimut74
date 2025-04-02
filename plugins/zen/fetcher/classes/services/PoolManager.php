<?php

namespace Zen\Fetcher\Classes\Services;

use Zen\Fetcher\Models\Pool;

class PoolManager
{
    private Pool $pool;

    public function __construct($pool_or_code)
    {
        if (is_string($pool_or_code)) {
            $this->pool = Pool::find($pool_or_code);
        } else {
            $this->pool = $pool_or_code;
        }
    }

    public function getModel(): Pool
    {
        return $this->pool;
    }

    public function settings(string $key = null, string $path = null)
    {
        $settings = $this->pool->pool_settings;
        if (!$settings) {
            return null;
        }

        $setting = collect($settings)->first(
            fn ($record) => $record['pool_settings_key'] === $key
        );

        if (!$setting) {
            return [];
        }

        $settings = fetcher()->fromJson($setting['pool_settings_data']);

        if ($path) {
            return data_get($settings, $path);
        }

        return $settings;
    }

    public function settingsUpdate(string $key, array $update_data = [])
    {
        $settings = $this->pool->pool_settings;
        $settings_exist = false;
        foreach ($settings as &$setting) {
            if ($setting['pool_settings_key'] === $key) {
                $settings_arr = fetcher()->fromJson($setting['pool_settings_data']);
                if (!$settings_arr) {
                    $settings_arr = [];
                }
                $settings_arr = array_merge($settings_arr, $update_data);
                $setting['pool_settings_data'] = fetcher()->toJson($settings_arr, true, true);
                $settings_exist = true;
                break;
            }
        }

        if (!$settings_exist) {
            $settings[] = [
                'pool_settings_key' => $key,
                'pool_settings_name' => $key,
                'pool_settings_data' => fetcher()->toJson($update_data, true, true)
            ];
        }

        $this->pool->pool_settings = $settings;
        $this->pool->save();
    }

    public function state(): array
    {
        $output = [];
        foreach ($this->pool->scenario as $method_instance) {
            if (boolval($method_instance['method_active'])) {
                $stream_code = $method_instance['path'];
                $stream = fetcher()->stream($stream_code);
                $stream_state = $stream->loadStreamState();
                if (!$stream_state) {
                    continue;
                }
                $stream_state['process'] = boolval($stream->getStreamPid());
                $stream_state['stream_code'] = $stream_code;
                $output[] = $stream_state;
            }
        }
        return $output;
    }

    public function activeState()
    {
        return collect($this->state())->first(fn($item) => $item['process']);
    }

    public function run(bool $force = false)
    {
        $pool = $this->pool;
        foreach ($pool->scenario as $method_instance) {
            if (boolval($method_instance['method_active'])) {
                $stream_code = $method_instance['path'];
                $method = \Str::afterLast($stream_code, '.');
                $class = str_replace(".$method", '', $stream_code);
                $class = str_replace(".", '\\', $class);
                $stream = fetcher()->stream($stream_code);
                $stream_state = $stream->loadStreamState();
                if (!$stream_state || $stream_state['success'] === false) {
                    if (!boolval($stream->getStreamPid())) {
                        if ($stream_state) {
                            if ($stream_state['error'] === null) {
                                $stream->streamRun();
                            } elseif ($force) {
                                $stream->dropError()->streamRun();
                            }
                        } else {
                            # Этот код запускает процесс который запускает поток
                            app($class)->{$method}($stream_code);
                        }
                        //sleep(3);
                        break;
                    }
                }
            }
        }
    }

    public function stop()
    {
        if ($active_state = $this->activeState()) {
            fetcher()->stream($active_state['stream_code'])->killStream();
        }
    }

    public function truncatePool()
    {
        $states = $this->state();
        foreach ($states as $state) {
            fetcher()->stream($state['stream_code'])->clearStream();
        }
    }
}
