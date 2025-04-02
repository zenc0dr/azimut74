<?php namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Zen\Worker\Classes\Http;
use Exception;

class GermesCruises extends Germes
{
    function cacheQueries()
    {
        $queries = [
            [
                'url' => 'https://river.sputnik-germes.ru/XML/ListClassKauta.php',
                'key' => 'germes.cabins'
            ],
            [
                'url' => 'https://river.sputnik-germes.ru/XML/ListKauta.php',
                'key' => 'germes.cabins.pivot'
            ],
            [
                'url' => 'https://river.sputnik-germes.ru/XML/ListTeplohod.php',
                'key' => 'germes.ships'
            ],
            [
                'url' => 'https://river.sputnik-germes.ru/XML/exportTur.php',
                'key' => 'germes.cruises'
            ],
        ];

        foreach ($queries as $query) {
            $this->stream->addJob('GermesCruises@cacheQuery', $query);
        }
    }

    function cacheQuery($data)
    {
        $url = $data['url'];
        $key = $data['key'];

        $response = $this->riverQuery($url, 'xml', null, $key);

        if ($response instanceof Exception) {
            throw new Exception($response);
        }
    }

    function addCruises()
    {
        $cruises = $this->stream->cache->get('germes.cruises');
        $cruises = $cruises['тур'];
        $ids = [];
        foreach ($cruises as $cruise)
        {
            #$id = $cruise['@attributes']['id'];
            $this->stream->addJob('GermesCruises@addCruise', $cruise);
        }
    }

    function addCruise($cruise)
    {
        $eds_id = $cruise['@attributes']['id'];
        $ship = $this->getGermesShipName($cruise);

        if(!$ship) {
            throw new Exception('В кэше отсутствует корабль');
        }

        $dates = $this->formatGermesDates($cruise);
        $checkin = Checkin::where('eds_code', 'germes')
            ->where('eds_id', $eds_id)
            ->first();

        if (!$checkin) {
            $this->daysDiffCheck($this->diffInIncompliteDays($dates->date, $dates->dateb), $eds_id);
            $waybill = $this->getGermesWaybill($cruise);
            $checkin = new Checkin;
            $checkin->date = $dates->date;
            $checkin->dateb = $dates->dateb;
            $checkin->desc_1 = '';
            $checkin->motorship_id = $ship->id;
            $checkin->active = 1;
            $checkin->eds_code = 'germes';
            $checkin->eds_id = (int) $eds_id;
            $checkin->waybill_id = $waybill;
            $checkin->save();
        } else {
            $checkin->date = $dates->date;
            $checkin->dateb = $dates->dateb;
            $checkin->waybill_id = $this->getGermesWaybill($cruise);
            $checkin->save();
        }

        $this->fixCheckin($checkin->id);

        $this->fillGermesPrices($eds_id, $checkin->id, $ship->id);
    }
}
