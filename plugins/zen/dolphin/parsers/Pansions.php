<?php namespace Zen\Dolphin\Parsers;

use Zen\Dolphin\Models\Pansion;
use Zen\Dolphin\Classes\Parser;

class Pansions extends Parser
{
    function go()
    {
        $this->parser_class_name = 'Pansions';
        $this->parser_name = 'Справочник питаний';
        $this->saveProgress('Запрос к источнику...');
        $records = $this->store('Dolphin')->attemps(5, 5, 15)->query('Pansions');
        $this->initCounts(count($records));
        $this->addPansions($records);
        $this->parserSuccess();
    }

    function addPansions($records)
    {
        foreach ($records as $record) {
            $this->db[$record['Id']] = [
                'eid' => $record['Id'],
                'name' => $record['Name'],
            ];
        }

        foreach ($this->db as $item) {
            $this->addPansion($item);
        }
    }

    function addPansion($item)
    {
        $pansion = Pansion::where('eid', $item['eid'])->first();
        if(!$pansion) {
            $pansion = new Pansion;
            $pansion->name = $item['name'];
            $pansion->eid = $item['eid'];
            $pansion->created_by = 'dolphin';
            $pansion->save();
        }
    }
}
