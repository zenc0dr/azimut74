<?php namespace Zen\Dolphin\Classes;

use Zen\Dolphin\Classes\Core;
use Carbon\Carbon;

class Stream extends Core # http://8ber.ru/s/97l
{
    private
        $state,
        $token,
        $storage,
        $memory
    ;

    /**
     * Stream constructor.
     * @param $token (string) - Ключ состояния потока в хранилище
     * @param $storage (string) - Код хранилища в Cabox
     * @param $job_generators_names (array || astring) - Имя в массиве или имена генераторов задач вида Dolphin.queryHandler
     */
    function __construct($token, $storage)
    {
        $this->token = $token;
        $this->storage = $this->cache($storage);
    }

    # Функция для запоминания данных ДО начала потока, далее их нельзя изменить
    function remember($value)
    {
        $this->memory = $value;
        return $this;
    }

    function launch($jobsGenerator)
    {
        $state = $this->storage->get($this->token);

        # Проверка на незавершённость
        $state = $this->checkUnderstudy($state);

        if($state) {
            $this->state = $state;
            # Получить данные и записать их в соответсвующие места
        } else {
            $this->createStream($jobsGenerator);
        }
        return $this;
    }

    # Проверка ранее запущенный и не завершённый поток
    private function checkUnderstudy($state)
    {
        if(!$state) return;

        $session_token = $this->sessionGet('dolphin_user_last_token');
        if($session_token && ($session_token != $this->token)) {
            # Тут выясняется что пользовтель начал новый запрос не дождавшись
            # Нужно узнать завершился ли процесс

            # Если процесс завершился всё ок
            if($state['end_time']) {
                return $state;
            }

            # Убить процесс и удалить состояние потока с диска
            $this->killProcess('dolphin:search --token='.$this->token);
            $this->cache('dolphin:search')->del($this->token);
            return false;
        }

        return $state;
    }

    function getMemory($key)
    {
        return @$this->state['memory'][$key];
    }

    function getJobsCount()
    {
        return count($this->state['jobs']);
    }

    # Получить количество выполненных задач
    function getProgress()
    {
        return count($this->state['data']);
    }

    # Получить Результаты
    function getAllResults()
    {
        $output = [];
        foreach ($this->state['data'] as $results) {
            if(!$results) continue;
            $output = array_merge($results);
        }
        return $output;
    }

    function getSuccess()
    {
        return (count($this->state['jobs']) === $this->getProgress());
    }

    # Функция создающая поток
    private function createStream($jobsGenerator)
    {

        $this->state = [
            'start_time' => time(),
            'end_time' => null,
            'jobs' => $jobsGenerator(),
            'data' => []
        ];

        if($this->memory) {
            $this->state['memory'] = $this->memory;
        }

        # Если задач нет, поток НЕ запустится
        if(!$this->state['jobs']) return;

        $this->saveState(); # Сохраняем состояние последний раз перед запуском
        $this->runStream();
    }

    # Запуск потока в бэкенде
    private function runStream()
    {
        $this->artisanExec('dolphin:search --token='.$this->token);
        $this->sessionPut('dolphin_user_last_token', $this->token);
    }

    function saveState()
    {
        $this->storage->put($this->token, $this->state);
    }

    ######################### CLI ##############################

    # Выполнить задачи потока
    function run()
    {
        $this->state = $this->storage->get($this->token);
        if(!$this->state) return;

        $job_index = 0;
        foreach ($this->state['jobs'] as $job)
        {

            if(isset($this->state['data'][$job_index])) {
                $job_index++;
                continue;
            }

            $handler = explode(':', $job['handler']);

            # Запрос к Store, например Dolphin:searchQuery
            $job_data = $this->store($handler[0])->{$handler[1]}($job['data']);

            $this->state['data'][$job_index] = $job_data;

            $this->saveState();
            $job_index++;
        }

        # Установка времени окончания выполенения задач потока
        $this->state['end_time'] = time();
        $this->saveState();
    }
}
