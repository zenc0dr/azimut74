<?php namespace Zen\Dolphin\Parsers;

use Zen\Dolphin\Classes\Parser;
use Zen\Dolphin\Models\RoomCategory;

class RoomCategories extends Parser
{
    function go()
    {
        $this->parser_class_name = 'RoomCategories';
        $this->parser_name = 'Категории номеров';
        $this->saveProgress('Запрос к источнику...');

        $rooms = $this->store('Dolphin')->attemps(5, 5, 15)->query('RoomCategories');
        $this->initCounts(count($rooms));

        $this->saveProgress('Получение данных...');

        foreach ($rooms as $room) {
            $this->addRoom($room);
            $this->saveProgress();
        }
        $this->parserSuccess();
    }

    function addRoom($record)
    {
        $room = RoomCategory::where('eid', $record['Id'])->first();
        if(!$room) {
            $room = new RoomCategory;
            $room->name = $record['Name'];
            $room->eid = $record['Id'];
            $room->created_by = 'dolphin';
            $room->save();
        }
    }
}
