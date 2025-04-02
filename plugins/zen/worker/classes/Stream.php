<?php namespace Zen\Worker\Classes;

use Zen\Worker\Models\Stream as StreamModel;
use Zen\Worker\Models\Job as JobModel;
use Zen\Cabox\Classes\Cabox;
use Exception;
use DB;

class Stream
{
    public
        $model, // Модель потока
        $state, // Состояние потока (Общее)
        $exception;

    public static function run(StreamModel $model)
    {
        ProcessLog::add("Запуск потока: {$model->name} [{$model->code}]");
        $stream = new self;
        $stream->model = $model;
        $stream->state = new State($stream);
        $stream->cache = new Cabox('worker');
        $stream->work();
        return $stream->exception;
    }

    # Обёртка для ProcessLog
    public function log($message)
    {
        ProcessLog::add($message);
    }

    # Добавить задачу
    public function addJob($call, $data = null)
    {
        $job = new JobModel;
        $job->stream_id = $this->model->id;
        $job->call = $call;
        if ($data) {
            $job->data = $data;
        }
        $job->save();
        ProcessLog::add("Поставлена задача id:{$job->id} Вызов: {$job->call}");
    }

    # Функция рекурсивная и запускает сама себя до завершения задач потока
    public function work()
    {
        $this->exception = null;
        $pool_state = $this->state->getActualPoolState();
        if ($pool_state === false) {
            ProcessLog::add("Поток завершил работу: {$this->model->name} [{$this->model->code}]");
            return;
        }

        ProcessLog::add("Запуск пула потока: " . $pool_state->call);
        $jobs_count = DB::table('zen_worker_jobs')->whereNull('error')->where('call', $pool_state->call)->count();

        # Пул требует задач но их нет
        if (!$jobs_count && !@$pool_state->self) {
            if ($pool_state->critical) {
                $this->exception = new Exception('Logic error');
            } else {
                $this->state->updatePoolState($pool_state, [
                    'errors_count' => 1,
                    'success' => true
                ]);

                $this->fixLastTime();

                $this->work();
            }

            return;
        }

        # Пул "самострел" - Не требует записсей в БД для запуска, ставит себя на примую в очередь
        if (!$jobs_count && @$pool_state->self) {
            $this->addJob($pool_state->call);
            $this->state->updatePoolState($pool_state, [
                'progress_to' => 0,
                'progress_of' => 1
            ]);
            $this->work();
            return;
        }

        # Первоначальная установка состояния пула
        if (!$pool_state->progress_of) {
            $this->state->updatePoolState($pool_state, [
                'progress_to' => 0,
                'progress_of' => $jobs_count
            ]);
        }

        $this->runJob($pool_state);
        $this->work();
    }

    public function fixLastTime()
    {
        # Запись в состояние события сессии
        $event = new Event('session', 'Сессия');
        $event->push([
            'last_time' => date('d.m.Y H:i:s'),
            'states' => Core::getStates()
        ]);
        $event->save();
    }

    public function runJob($pool_state)
    {
        $job = JobModel::whereNull('error')->where('call', $pool_state->call)->orderBy('id')->first();

        # Задач больше нет, пул задач выполнен
        if (!$job) {
            $this->state->updatePoolState($pool_state, [
                'success' => true,
            ]);
            $this->fixLastTime();
            return;
        }

        # Задача выполняется
        $exception = JobProcessor::run($job, $this, $pool_state);

        # Если задача критическая то прекратить поток
        if ($exception instanceof Exception) {
            if ($pool_state->critical) {
                $this->exception = $exception;
                return;
            }

            $pool_state->errors_count++;

            # Счётчик ошибок прибавлен
            $this->state->updatePoolState($pool_state, [
                'errors_count' => $pool_state->errors_count,
            ]);
        }

        # Обновление прогресса
        $this->state->updatePoolState($pool_state);

        unset($job);
        $this->runJob($pool_state);
    }
}
