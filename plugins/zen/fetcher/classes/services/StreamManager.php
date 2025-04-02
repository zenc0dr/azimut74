<?php

namespace Zen\Fetcher\Classes\Services;

class StreamManager
{
    private string $stream_key; # Уникальный ключ потока
    private ?array $state = null; # Состояние потока
    private ?string $batch_handler = null;
    private ?array $batch_handler_attributes = null;
    private string $output = '/dev/null';
    private string $output_errors = '/dev/null';
    private int $batch_count = 0;
    private int $max_runtime = 60; # Максимальное время выполнения цепочки процессов (секунд)

    public function __construct(string $stream_key)
    {
        $this->stream_key = $stream_key;
    }

    # Первым делом создаём поток
    public function streamRun(bool $save_stream_before = true)
    {
        if ($save_stream_before) {
            $this->saveStreamState();
        }

        $php_path = fetcher()->settings('php_path');
        if ($php_path === 'ENV') {
            $php_path = env('PHP_PATH');
        }

        $this->killStream();

        $dir = base_path();
        $output = $this->output;
        $output_errors = $this->output_errors;
        $cli_command = "nohup $php_path $dir/artisan fetcher Stream.run ";
        $cli_command .= "--input=$this->stream_key >$output 2>$output_errors &";
        exec($cli_command);
        return $cli_command;
    }

    public function dropError()
    {
        if ($this->state['error'] !== null) {
            $this->state['error'] = null;
            $this->state['error_time'] = null;
        }
        return $this;
    }

    public function getStreamPid(): int
    {
        $entry = $this->stream_key;
        $entry_preg = preg_quote($entry);
        exec("ps aux|grep $entry_preg", $processes);
        $process = false;

        foreach ($processes as $line) {
            if (!str_contains($line, "grep $entry") && str_contains($line, $entry)) {
                $process = $line;
                break;
            }
        }

        if (!$process) {
            return 0;
        }

        $process = preg_replace('/ +/', ' ', $process);
        $process = explode(' ', $process);

        # Return pid
        return intval($process[1]);
    }

    public function killStream()
    {
        if ($pid = $this->getStreamPid()) {
            exec("kill -9 $pid");
            $this->killStream(); # На случай если вдруг процесс дублируется
        }
    }

    private function timeFormatted(\Carbon\Carbon $carbon): string
    {
        return $carbon->format('d.m.Y H:i:s');
    }

    private function saveStreamState()
    {
        if ($this->state === null) {
            $this->state = [
                'batch_handler' => $this->batch_handler,
                'no_batches' => !boolval($this->batch_count),
                'max_runtime' => $this->max_runtime,
                'batches_total_count' => $this->batch_count,
                'batches_processed_count' => 0,
                'time_start' => $this->timeFormatted(now()),
                'time_latest' => null,
                'time_end' => null,
                'error' => null,
                'error_time' => null,
                'success' => false
            ];
        }

        fetcher()
            ->files()
            ->arrayToFile(
                $this->state,
                storage_path(
                    "fetcher/streams/$this->stream_key.state.json"
                )
            );
    }

    public function loadStreamState()
    {
        if ($this->state === null) {
            $this->state = fetcher()
                ->files()
                ->arrayFromFile(
                    storage_path(
                        "fetcher/streams/$this->stream_key.state.json"
                    )
                );
            return $this->state;
        }
        return null;
    }

    public function setRuntime(int $sec): self
    {
        $this->max_runtime = $sec;
        return $this;
    }

    public function addHandler(string $handler): self
    {
        $this->batch_handler = $handler;
        return $this;
    }

    public function clearStream()
    {
        $this->killStream();
        $stream_state_path = storage_path(
            "fetcher/streams/$this->stream_key.state.json"
        );

        if (file_exists($stream_state_path)) {
            unlink($stream_state_path);
        }
    }

    public function addBatch(array $batch): self
    {
        fetcher()
            ->files()
            ->arrayToFile(
                $batch,
                storage_path(
                    "fetcher/streams/$this->stream_key.$this->batch_count.json"
                )
            );
        $this->batch_count++;
        return $this;
    }

    public function addBatches(array $batches): self
    {
        foreach ($batches as $batch) {
            $this->addBatch($batch);
        }
        return $this;
    }

    private function getBatch()
    {
        if ($this->state['no_batches']) {
            return intval($this->state['batches_processed_count']);
        }
        return fetcher()
            ->files()
            ->arrayFromFile(
                storage_path(
                    "fetcher/streams/$this->stream_key.$this->batch_count.json"
                )
            );
    }

    private function handlerPathFormatter(): array
    {
        if ($this->batch_handler_attributes) {
            return $this->batch_handler_attributes;
        }
        $handler = $this->state['batch_handler'];
        if (!$handler) {
            throw new \Exception('Не указан обработчик');
        }
        $method = \Str::afterLast($handler, '.');
        $handler = str_replace(".$method", '', $handler);
        $handler = str_replace(".", '\\', $handler);

        return $this->batch_handler_attributes = [
            'class' => $handler,
            'method' => $method
        ];
    }

    /**
     * Запуск одного процесса
     */
    public function run()
    {
        $stop_stream = false;
        $stream_result = null;
        $runtime = time();
        $time_now_formatted = $this->timeFormatted(now());
        $this->loadStreamState(); # Состояние всегда загружается (если оно не null)
        if ($this->state['error'] !== null) {
            # Поток с ошибкой был запущен по ошибке
            return;
        }
        $batch = $this->getBatch();

        try {
            $handler = $this->handlerPathFormatter();
            $stream_result = app($handler['class'])->{$handler['method']}($batch);
        } catch (\Exception $ex) {
            $this->state['error'] = $ex->getMessage() . ' -- ' . $ex->getFile() . ':' . $ex->getLine();
            $this->state['error_time'] = $this->timeFormatted(now());
        }

        $this->state['time_latest'] = $time_now_formatted;
        if ($this->state['error'] === null) {
            $this->state['batches_processed_count']++;

            # Если есть пакеты то считаем и прерываем по их окончанию
            if (!$this->state['no_batches']) {
                if ($this->state['batches_processed_count'] >= $this->state['batches_total_count']) {
                    $this->state['success'] = true;
                    $this->state['time_end'] = $time_now_formatted;
                    $stop_stream = true; # Завершение цепочки потока
                }
            }

            # Прерывание потока результатом пакета
            if ($stream_result === false) {
                $this->state['success'] = true;
                $this->state['time_end'] = $time_now_formatted;
                $stop_stream = true; # Завершение цепочки потока
            }
        } else {
            $stop_stream = true; # Остановить цепочку потока в случае ошибки
        }

        $this->saveStreamState(); # Состояние всегда сохраняется

        # Триггер остановки
        if ($stop_stream) {
            $this->finishStream();
            return;
        }

        $runtime = time() - $runtime;

        if ($runtime < $this->state['max_runtime']) {
            $this->run();
        } else {
            $this->streamRun(false);
        }
    }

    private function finishStream()
    {
        fetcher()->console('Dispatcher.run');
    }
}
