<?php namespace Zen\Worker\Api;

use Zen\Worker\Classes\Core;
use Zen\Worker\Models\Stream as Stream;
use Yaml;
use Zen\Worker\Classes\Event;


class Admin extends Core
{

    private $token = 'xDenBhdu6fTgd3nbbBd45oOpd6gGssX';

    # http://azimut74/zen/worker/api/admin:state
    function state()
    {

        $streams = Stream::active()->orderBy('sort_order')->get();
        $state_dir = storage_path('worker');

        $state = [];

        foreach ($streams as $stream) {
            $dy_state_path = $state_dir . "/{$stream->code}State.yaml";
            $states = @Yaml::parseFile($dy_state_path);

            if(!$states) {
                break;
            }

            $i = 0;
            $pool = [];
            foreach ($stream->pools as $stream_pool) {
                $pool[] = array_merge($stream_pool, $states[$i]);
                $i++;
            }

            $state[] = [
                'name' => $stream->name,
                'pools' => $pool
            ];
        }

        $this->json([
            'state' => $state,
            'process' => $this->processIsLaunched('worker:go')
        ]);
    }

    # http://azimut74/zen/worker/api/admin:go
    function go()
    {
        $token = $this->input('token');

        if(!$this->isAdmin(true)) {
            if($token !== $this->token) {
                die('access error');
            }
        }

        if(!$this->processIsLaunched('worker:go')) {
            $this->artisanExec('worker:go');
        } else {
            if(!$token) {
                $this->killProcess('worker:go');
            }
        }
    }

    # http://azimut74/zen/worker/api/admin:wakeUp
    function wakeUp()
    {
        if(!Event::hasOpenSession()) {
            echo 'Not open sessions';
            return;
        }

        if(!$this->processIsLaunched('worker:go')) {
            $this->artisanExec('worker:go');
        } else {
            echo 'Worker is running';
        }
    }

    # http://azimut74/zen/worker/api/admin:transfer
    # Параметры: source (gama|germes|infoflot|volga|waterway|all), validate-only (bool), skip-validation (bool)
    function transfer()
    {
        $token = $this->input('token');

        if(!$this->isAdmin(true)) {
            if($token !== $this->token) {
                die('access error');
            }
        }

        $source = $this->input('source');
        $validateOnly = $this->input('validate-only', 'bool');
        $skipValidation = $this->input('skip-validation', 'bool');

        // Формируем команду
        $command = 'worker:transfer';
        if ($source) {
            $command .= ' --source=' . escapeshellarg($source);
        }
        if ($validateOnly) {
            $command .= ' --validate-only';
        }
        if ($skipValidation) {
            $command .= ' --skip-validation';
        }

        // Проверяем, не запущена ли уже команда
        $processName = 'worker:transfer' . ($source ? ':' . $source : '');
        if($this->processIsLaunched($processName)) {
            $this->json([
                'success' => false,
                'message' => 'Команда уже запущена',
                'command' => $command
            ]);
            return;
        }

        // Запускаем команду в фоне
        $this->artisanExec($command);

        $this->json([
            'success' => true,
            'message' => 'Команда запущена в фоновом режиме',
            'command' => $command,
            'process' => $processName
        ]);
    }

}
