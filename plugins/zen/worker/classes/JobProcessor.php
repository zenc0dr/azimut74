<?php namespace Zen\Worker\Classes;

use Predis\Response\Error;
use Zen\Worker\Models\Job as JobModel;
use Exception;
use Zen\Worker\Models\ErrorLog;

class JobProcessor
{
    private $model, $stream, $pool_state, $attempts = 0;

    public static function run(JobModel $job_model, Stream $stream, $pool_state)
    {
        $job = new self;
        $job->stream = $stream;
        $job->pool_state = $pool_state;
        $job->model = $job_model;
        return $job->work();
    }

    public function work()
    {
        $job = $this->model;
        $exception = null;
        //try {
            $call = $job->call;
            if (preg_match('/^[a-zA-Z0-9]+@[a-zA-Z0-9]+$/', $call)) {
                $call = 'Zen\Worker\Pools\\' . $call;
            }

            $call = explode('@', $call);

            # Выполнение задачи
            $job_object = app($call[0]);
            $job_object->stream = $this->stream;
            $job_object->pool_state = $this->pool_state;
            $job_object->{$call[1]}($job->data);
            ProcessLog::add("Задача выполнена: id:{$job->id} {$job->call}");

            # Удаление задачи
            $job->delete();
//        } catch (Exception $ex) {
//            $exception = $ex;
//            $this->attempts++;
//            $line = $ex->getLine();
//            $message = $ex->getMessage();
//            $file = $ex->getFile();
//            $job->error = "Ошибка: $message в файле $file:$line";
//            $job->save();
//
//            if ($this->attempts > $this->pool_state->attempts) {
//                ProcessLog::add("Задача не может быть обработана и будет пропущена");
//                $error_job = new ErrorLog;
//                $error_job->fill($job->makeHidden('id')->toArray());
//                $error_job->save();
//            } else {
//                $pause = @$this->pool_state->pause ?? 5;
//                $file = str_replace(base_path(), '', $file);
//                ProcessLog::add("Задача завершилась с ошибкой: $message в файле $file:$line");
//                ProcessLog::add("Дополнительная попытка [{$this->attempts} из {$this->pool_state->attempts}] через $pause сек.");
//
//                if ($pause) {
//                    sleep($pause);
//                }
//                $this->work();
//            }
//        }

        return $exception;
    }
}
