<?php namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Zen\Worker\Classes\Http;
use Exception;
use DB;

class GamaCruises extends Gama
{
    public array $prices = [];

    function getCruises()
    {
        $response = $this->stream->cache->get('GamaCruises.getCruises');
        if (!$response) {
            $http = new Http;
            $http_query = $http->setTimout($this->pool_state->timeout)
                ->query('https://gama-nn.ru/execute/navigation', 'xml');

            if ($http_query->error) {
                throw new Exception($http_query->error);
            }

            $response = $http_query->response;
            $this->stream->cache->put('GamaCruises.getCruises', $response);
        }

        $cruises = $response['navigations']['navigation'];
        $ids = [];
        foreach ($cruises as $cruise) {
            if (!isset($cruise['ways']['way'])) {
                continue;
            }
            foreach ($cruise['ways']['way'] as $way) {

                if (isset($way['@attributes']['iid'])) {
                    $eds_id = $way['@attributes']['iid'];
                } else {
                    $eds_id = $way['iid'];
                }

                $ids[] = $eds_id;
            }
        }

        foreach ($ids as $id) {
            $this->stream->addJob('GamaCruises@addCruise', ['id' => $id]);
        }
    }

    function addCruise($data)
    {
        $response = $this->stream->cache->get('GamaCruises.getCruises');
        $cruises = $response['navigations']['navigation'];
        $id = $data['id'];

        $cruise_arr = $this->getGamaCruise($id, $cruises);
        $gama_ship_id = $cruise_arr['gama_ship_id'];
        $gama_ship_name = $cruise_arr['gama_ship_name'];
        $way = $cruise_arr['way'];
        $date = $way['@attributes']['STS'];
        $dateb = $way['@attributes']['FTS'];
        $status_way = $way['@attributes']['Way'];

        $http = new Http;
        $http_query = $http->setTimout($this->pool_state->timeout)
            ->query('https://gama-nn.ru/execute/way/' . $id . '/all', 'xml');

        if ($http_query->error) {
            throw new Exception($http_query->error);
        }

        $gama_cruise = $http_query->response;

        $checkin = Checkin::where('eds_code', 'gama')
            ->where('eds_id', $id)
            ->first();

        if (!$checkin) {
            $ship = $this->getMotorship($gama_ship_name, 'gama_id', $gama_ship_id);
            if ($ship === false) return;

            $waybill = $this->gamaRouteBolder($status_way, $gama_cruise);
            $desc_1 = $this->gamaDesignSchedule($gama_cruise);

            $this->daysDiffCheck($this->diffInIncompliteDays($date, $dateb), $id);

            $checkin = new Checkin;
            $checkin->date = $date;
            $checkin->dateb = $dateb;

            $checkin->desc_1 = $desc_1;
            $checkin->motorship_id = $ship->id;
            $checkin->active = 1;
            $checkin->eds_code = 'gama';
            $checkin->eds_id = (int)$id;
            $checkin->waybill_id = $waybill;
            $checkin->save();
        } else {
            ### UPDATE WAYBILLS ###
            $updated_waybill = $this->gamaRouteBolder($status_way, $gama_cruise);
            $checkin->waybill_id = $updated_waybill;
            #######################
            ## Обновление расписаний ##
            $checkin->desc_1 = $this->gamaDesignSchedule($gama_cruise);
            $checkin->save();
            #######################
        }

        $this->fixCheckin($checkin->id);

        $gama_cabins = false;
        if (isset($gama_cruise['cabins']['cabin'])) {
            $gama_cabins = $gama_cruise['cabins']['cabin'];
            if (isset($gama_cabins['@attributes'])) {
                $gama_cabins = [$gama_cabins];
            }
        }

        if ($gama_cabins) {

            foreach ($gama_cabins as $gama_cabin) {

                $gama_cabin_name = false;

                $gama_cabin_name = $this->getGamaParam($gama_cabin, 'category_name');
                $gama_cabin_id = $this->getGamaParam($gama_cabin, 'category_iid');
                $gama_cabin_name .= '|' . $gama_cabin_id;

                if ($this->isCabinNotLet($gama_cabin_name, $checkin->motorship_id)) continue;

                if (!$gama_cabin_name) {
                    continue;
                }

                $cabin = Cabin::where('motorship_id', $checkin->motorship->id)
                    ->where('gama_name', $gama_cabin_name)->first();

                if (!$cabin)
                    $cabin = Cabin::where('motorship_id', $checkin->motorship->id)
                        ->where('category', $gama_cabin_name)->first();


                $gama_deck_id = $this->getGamaParam($gama_cabin, 'deck');

                if (!$gama_deck_id) {
                    continue;
                }

                $deck = $this->getGamaDeck($gama_deck_id);

                if (!$cabin) {
                    $cabin = new Cabin;
                    $cabin->motorship_id = $checkin->motorship->id;
                    $cabin->category = $gama_cabin_name;
                    $cabin->gama_name = $gama_cabin_name;
                    $cabin->desc = '';
                    $cabin->save();
                }

                $this->deckPivotCheck($cabin->id, $deck->id);
                $this->fillGammaPrices($gama_cabin, $cabin, $checkin);
                $prices_insert = $this->insertPrices($cabin, $checkin);
                if ($prices_insert) {
                    $checkin->active = 1;
                    $checkin->save();
                    #echo "Статус изменён на active=1\n";
                }
            }
        }
    }

    public function fillGammaPrices($gama_cabin, $cabin, $checkin)
    {
        $prices = [];

        foreach ($gama_cabin['cost'] as $price) {
            $price_value = intval($this->getGamaParam($price, 'std3'));
            if ($price_value) {
                $prices[] = $price_value;
            }
        }

        if (!$prices) {
            return;
        }

        $price = min($prices);

        $this->prices[] = [
            'checkin_id' => $checkin->id,
            'cabin_id' => $cabin->id,
            'price_a' => $price
        ];
    }

    public function insertPrices($cabin, $checkin)
    {
        if (!$this->prices) {
            return false;
        }

        //echo "Вставка цен гамма\n";

        DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $checkin->id)
            ->where('cabin_id', $cabin->id)
            ->delete();

        DB::table('mcmraak_rivercrs_pricing')
            ->insert($this->prices);
        $this->prices = [];
        return true;
    }
}
