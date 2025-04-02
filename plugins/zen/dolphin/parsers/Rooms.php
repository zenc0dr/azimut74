<?php namespace Zen\Dolphin\Parsers;

use Zen\Dolphin\Classes\Parser;

use Zen\Dolphin\Models\Room;

class Rooms extends Parser
{
    function go()
    {
        $this->parser_class_name = 'Rooms';
        $this->parser_name = 'Типы номеров';
        $this->saveProgress('Запрос к источнику...');

        $rooms = $this->store('Dolphin')->attemps(5, 5, 15)->query('Rooms');
        $this->initCounts(count($rooms));

        $this->saveProgress('Получение данных...');

        foreach ($rooms as $room) {
            $this->addRoom($room);
        }
        $this->parserSuccess();
    }

    function addRoom($record)
    {
        $room = Room::where('eid', $record['Id'])->first();
        if(!$room) {
            $room = new Room;
            $room->name = $record['Name'];
            $room->eid = $record['Id'];
            $room->created_by = 'dolphin';
            $room->save();
        }
    }
}
