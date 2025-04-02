<?php namespace Zen\Dolphin\Parsers;

use Zen\Dolphin\Classes\Parser;

class Tours extends Parser
{
    function go()
    {
        $this->parser_class_name = 'Tours';
        $this->parser_name = 'Туры';

        $parsers_cache = $this->cache('dolphin.parsers');

        $tours_eids = [];

        $this->saveProgress('Собирается коллекция идентификаторов Dolphin...');

        $parsers_cache->handleItems(function ($data) use (&$tours_eids) {
            $value = $data['value']['response'];
            $tours = @$value['Tours'];
            if($tours) {
                foreach ($tours as $tour) {
                    $tours_eids[$tour['Id']] = $tour['Id'];
                }
            }
        });

        asort($tours_eids);
        $tours_eids = array_values($tours_eids);

        $dolphin = $this->store('Dolphin');
        $this->initCounts(count($tours_eids));

        foreach ($tours_eids as $tour_eid) {
            $dolphin->getTour($tour_eid, true);
            $this->saveProgress();
        }

        $this->parserSuccess();
    }
}
