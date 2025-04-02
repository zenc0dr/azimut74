<?php namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Cabins as Cabin;

class Germes extends RiverCrs
{
    function getGermesShipName($cruise)
    {
        $germes_ship_id = $cruise['Теплоход'];
        $germes_ships = $this->stream->cache->get('germes.ships');
        $germes_ships = $germes_ships['Теплоход'];

        foreach ($germes_ships as $item)
        {
            if ($item['id'] == $germes_ship_id) {
                $germes_ship_name = $item['Название'];
                $ship = $this->getMotorship($germes_ship_name,'germes_id', $germes_ship_id);
                return $ship;
            }
        }
        return false;
    }

    function formatGermesDates($cruise)
    {
        $d_a = $cruise['ДатаОтплытия'];
        $d_a = $this->mutatorGermesDate($d_a);
        $t_a = $cruise['ВремяОтплытия'];
        $date = $d_a.' '.$t_a.':00';

        $d_b = $cruise['ДатаПрибытия'];
        $d_b = $this->mutatorGermesDate($d_b);
        $t_b = $cruise['ВремяПрибытия'];
        $dateb = $d_b.' '.$t_b.':00';

        return (object) [
            'date' => $date,
            'dateb' => $dateb,
        ];
    }

    # Мутатор даты из 08.05.2018 в 2018-05-08
    public function mutatorGermesDate($date)
    {
        $i = explode('.', $date);
        return $i[2].'-'.$i[1].'-'.$i[0];
    }

    function getGermesWaybill($cruise)
    {
        $germes_cruise_id = $cruise['@attributes']['id'];
        $germes_trace = $this->riverQuery('https://river.sputnik-germes.ru/XML/exportTrace.php', 'xml', ['tur' => $germes_cruise_id]);

        preg_match_all('/<span[^>]+>(.+)<\/span>/', $cruise['Маршрут'], $bold_towns);
        $trace = $germes_trace['Tour']['City'];
        $waybill = [];
        $key = 0;
        $max = count($trace) - 1;
        foreach ($trace as $town_name)
        {
            $town_id = $this->getTownId($town_name, 'germes');
            if($bold_towns[1]) {
                $waybill[] = [
                    'town' => $town_id,
                    'excursion' => '',
                    'bold' => (in_array($town_name, $bold_towns[1]) || $key==0 || $key == $max)?1:0
                ];
            } else {
                $waybill[] = [
                    'town' => $town_id,
                    'excursion' => '',
                    'bold' => ($key==0 || $key == $max)?1:0
                ];
            }
            $key++;
        }
        return $waybill;
    }

    function fillGermesPrices($germes_cruise_id, $checkin_id, $ship_id)
    {
        $ship_id = intval($ship_id);

        $prices = $this->riverQuery('https://river.sputnik-germes.ru/XML/exportKauta.php', 'xml', ['tur' => $germes_cruise_id]);

        $cabins = $this->stream->cache->get('germes.cabins');
        $pivot = $this->stream->cache->get('germes.cabins.pivot');

        $del_q = [];
        $ins_q = [];

        foreach ($prices['Каюта'] as $germes_price)
        {
            $price_id = $germes_price['id'];

            $cabin = $this->getGermesCabinClass($price_id, $cabins, $pivot);
            if(!$cabin) continue;

            if($this->isCabinNotLet($cabin['Название'], $ship_id)) continue;

            $price_value = (int) $germes_price['ЦенаОснМест'];
            if(!$price_value) continue;
            $cabin_id = $this->getGermesCabin($cabin, $ship_id);
            $queryes = $this->updateCabinPrice($checkin_id, $cabin_id, (int) $price_value, null, 1);
            $del_q[] = $queryes['del'];
            $ins_q[] = $queryes['ins'];
        }

        $del_q = array_unique($del_q);
        $ins_q = array_unique($ins_q);
        $del_q = join(';', $del_q);
        $ins_prefix = 'INSERT INTO `mcmraak_rivercrs_pricing` (`checkin_id`, `cabin_id`, `price_a`, `price_b`, `desc`) VALUES ';
        $ins_q = $ins_prefix . join(',', $ins_q).';';

        \DB::unprepared($del_q);
        \DB::unprepared($ins_q);
    }

    function getGermesCabinClass($price_id, $cabins, $pivot)
    {
        $idClassKauta = false;
        foreach ($pivot['Kauta'] as $pivot_item)
        {
            $id = $pivot_item['@attributes']['id'];
            if($price_id == $id) {
                $idClassKauta = $pivot_item['@attributes']['idClassKauta'];
                continue;
            }
        }

        if(!$idClassKauta) {
            return false;
        }

        foreach ($cabins['Класс'] as $cabinClass)
        {
            $id = $cabinClass['@attributes']['id'];
            if($idClassKauta == $id) {
                return $cabinClass;
            }
        }
        return false;
    }

    function getGermesCabin($germes_cabin, $ship_id)
    {

        $germes_cabin_name = $germes_cabin['Название'];
        $germes_cabin_name = trim($germes_cabin_name);
        $germes_cabin_desc = $germes_cabin['Описание'];

        # Есть ли уже эта кабина с кодом источника ?
        $cabin = Cabin::where('germes_name', $germes_cabin_name)
            ->where('motorship_id', $ship_id)
            ->first();

        # Если есть возвращаем id
        if($cabin) {
            return $cabin->id;
        }


        $cabin = Cabin::where('germes_name', $germes_cabin_name)
            ->where('motorship_id', $ship_id)
            ->first();

        if(!$cabin)
            $cabin = Cabin::where('category', $germes_cabin_name)
                ->where('motorship_id', $ship_id)
                ->first();

        # Если есть, добавляем код источника и возвращаем id
        if($cabin) {
            $cabin->germes_name = $germes_cabin_name;
            $cabin->save();
            return $cabin->id;
        }

        # Если нет проверям описание
        if(is_array($germes_cabin_desc)){
            $germes_cabin_desc = join('', $germes_cabin_desc);
        }

        # Если нет создаём новую кабину
        $cabin = new Cabin;
        $cabin->motorship_id = $ship_id;
        $cabin->category = $germes_cabin_name;
        $cabin->germes_name = $germes_cabin_name;
        $cabin->desc = $germes_cabin_desc;
        $cabin->save();
        return $cabin->id;
    }
}
