<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Classes\Parser;
use Mcmraak\Rivercrs\Classes\CacheSettings as CS;
use View;
use Input;

class GermesSchelude
{
    # /rivercrs/debug_germes_schelude?cruise_id=12544
    public function render($checkin=null, $return = false)
    {
        $debug = false;

        if(Input::get('cruise_id')) {
            $debug = Input::get('cruise_id');
        }

        if(!$debug) {
            if($checkin->eds_code!='germes') return;
            $cruise_id = $checkin->eds_id;
            if(!$cruise_id) return;
        } else {
            $cruise_id = $debug;
        }


        $cache_time = intval(CS::get('germes_trace'));

        $parser = new Parser;
        $trace = $parser->get('xmlx', 'https://river.sputnik-germes.ru/XML/exportTrace.php', [
            'tur' => $cruise_id,
            'shelude' => 'true'
        ], $cache_time);

        $city = $trace['CITY'];

        $trace_table = [];
        foreach ($city as $item) {
            $trace_table[] = [
                'date_arrive' => $this->getAttr($item, 'DATEARRIVE'),
                'date_depart' => $this->getAttr($item, 'DATEDEPART'),
                'time_arrive' => $this->getAttr($item, 'TIMEARRIVE'),
                'time_depart' => $this->getAttr($item, 'TIMEDEPART'),
                'time_diff' => $this->getAttr($item, 'TIMEDIFF'),
                't1' => $this->getAttr($item, 'T1'),
                't2' => $this->getAttr($item, 'T2'),
                'value' => $item['value']
            ];
        }

        $cache_time = intval(CS::get('germes_excursion'));
        #$cache_time = 0; #DEBUG

        $excursion = $parser->get('xmlv', 'https://river.sputnik-germes.ru/XML/exportExcursion.php', [
            'tur' => $cruise_id,
            'shelude' => 'true'
        ], $cache_time);


        $excursion_table = [];
        $city = '';
        foreach ($excursion as $item) {
            if($item['tag']!='CITY' && $item['tag']!='EXCURSION') continue;
            if($item['tag']=='CITY') {
                $city = $this->getAttr($item, 'NAME');
                continue;
            }

            $excursion_table[$city][] = [
                'name' => $this->getAttr($item, 'NAME'),
                'duration' => intval($this->getAttr($item, 'DURATION')),
                'price_adult' => $this->getAttr($item, 'PRICEADULT'),
                'price_child' => $this->getAttr($item, 'PRICECHILD'),
                'desc' => $item['value'] ?? null,
            ];
        }

        if($debug) {
            dd($trace_table, $excursion_table);
        }

        if($return) {
            return [
                'trace_table' => $trace_table,
                'excursion_table' => $excursion_table
            ];
        }

        return View::make('mcmraak.rivercrs::germes.schelude',
            [
                'trace_table' => $trace_table,
                'excursion_table' => $excursion_table
            ]
        );

    }

    public function getAttr($item, $attrName)
    {
        return isset($item['attributes'][$attrName])?$item['attributes'][$attrName]:'';
    }
}
