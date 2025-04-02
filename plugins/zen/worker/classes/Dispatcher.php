<?php

namespace Zen\Worker\Classes;

set_time_limit(0);

use Zen\Worker\Models\Stream as StreamModel;
use Zen\Worker\Classes\Core;
use Exception;
use DB;

class Dispatcher
{
    # Данный класс запускает поочерёдно потоки, если поток завершился критической ошибкой то
    # поток пропускается и запускается следующий, и так до тех пор пока система не придёт в одно из двух
    # состояний: 1 - Все потоки завершили работу 2 - Остались только потоки завершённые с критическими ошибками
    # TODO: Но пока просто по очерёдно

    public static function run()
    {
        # Функция очистки перед работой (Для отладки)
        $start_clean = false;

        $streams = StreamModel::active()->get();

        if ($start_clean) { # TODO: Для отладки
            DB::table('zen_worker_jobs')->truncate();
            DB::table('zen_worker_errors')->truncate();
            foreach ($streams as $stream) {
                $stream->clearState();
            }
        }

        ProcessLog::add("Запуск диспетчера потоков [Потоков: {$streams->count()}]");

        # Фиксация начала сессии
        $event = new Event('session', 'Сессия');


        # Либо $event создался новый и он success = false
        if ($event->new) {
            # Очистит состояние сессии
            Core::cleanWorkerSession();

            $event->push([
                'start_time' => date('d.m.Y H:i:s')
            ]);
            $event->save();
        }


        foreach ($streams as $stream) {
            $exception = Stream::run($stream);
            if ($exception instanceof Exception) {
                ProcessLog::add("Поток: {$stream->name} {$stream->code} пропущен из за критической ошибки");
            }
        }

        $event = new Event('session', 'Сессия');

        # Фиксация окончания сессии
        $event->push([
            'end_time' => date('d.m.Y H:i:s'),
            'states' => Core::getStates()
        ]);
        $event->success(1);
        $event->save();
    }
}
