<?php namespace Zen\Worker\Pools;

use Carbon\Carbon;
use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Zen\Worker\Classes\Http;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use DB;
use Mcmraak\Rivercrs\Patches\PricePatch;
use Zen\Worker\Classes\ProcessLog;

class WaterwayCruises extends Waterway
{
    public function getCruises()
    {
        // https://api-crs.vodohod.com/docs/agency/#tag/CRUISES-AND-MOTORSHIPS/paths/~1json~1v3~1cruises/get

        $now_day = Carbon::parse(now()->format('Y-m-d 00:00:00'))->timestamp;

        $batch = 100;
        $offset = 0;
        $count = null;

        while (true) {
            $query_parameters = [
                'limit' => $batch,
                'durationFrom' => 2,
                'dateFrom' => $now_day,
                'offset' => $offset
            ];

            $test_query = json_encode(['query' => http_build_query($query_parameters)]);

            $job_exists = DB::table('zen_worker_jobs')
                ->where('data', $test_query)
                ->count();

            if ($job_exists) {
                $offset += 100;
                continue;
            }

            $query_parameters = http_build_query($query_parameters);
            $api_url = "json.v3.cruises?$query_parameters";
            $cache_key = md5($api_url.$query_parameters);
            $cruises = $this->wwQuery($api_url, null, "waterway.cruises::$cache_key");

            $this->stream->addJob('WaterwayCruises@wwBatchCruisesHandler', ['query' => $query_parameters]);
            $offset += 100;

            if ($count === null) {
                $count = $cruises['result']['count'];
            }
            $count -= $batch;
            if ($count <= 0) {
                break;
            }
        }
    }

    public function wwBatchCruisesHandler($data)
    {
        $query_parameters = $data['query'];
        $api_url = "json.v3.cruises?$query_parameters";
        $cache_key = md5($api_url.$query_parameters);
        $cruises = $this->wwQuery($api_url, null, "waterway.cruises::$cache_key");
        $cruises = $cruises['result']['data'];

        $chek_time = now(); // На неделю назад, на всякий случай.
        foreach ($cruises as $cruise) {
            $date_start = Carbon::parse($cruise['dateStart']);
            if ($date_start < $chek_time) {
                continue;
            }
            $this->stream->addJob('WaterwayCruises@addCruise', ['cruise_id' => $cruise['id']]);
        }
    }

    private function specLog(array $data)
    {
        $data = json_encode($data, 128 | 256);

        file_put_contents(
            storage_path('logs/waterway.log'),
            $data . PHP_EOL,
        );
    }

    public function addCruise($data)
    {
        // https://api-crs-stage.vodohod.com/json/v3/cruise/room-tariffs?id=1

        $ww_cruise_id = $data['cruise_id'];

        #TODO(zenc0rd): Debug
//        if ($ww_cruise_id !== 20625) {
//            return;
//        }

        $ww_cruise = $this->wwQuery("json.v3.cruise?id=$ww_cruise_id", null, "waterway.cruise.$ww_cruise_id");
        $ww_cruise = $ww_cruise['result'];

//        dd(
//            storage_path("logs/waterway_cruise_prices_$ww_cruise_id.log")
//        );

//        file_put_contents(
//            storage_path("logs/waterway_cruise_$ww_cruise_id.log"),
//            json_encode($ww_cruise, 128 | 256),
//        );

        $query_key = "json.v3.cruise.room-tariffs?id=$ww_cruise_id";

        $cruise_prices = $this->wwQuery($query_key, null, "waterway.price.$ww_cruise_id");

//        file_put_contents(
//            storage_path("logs/waterway_cruise_prices_$ww_cruise_id.log"),
//            json_encode($cruise_prices, 64 | 128 | 256),
//        );

        $checkin = Checkin::where('eds_code', 'waterway')
            ->where('eds_id', $ww_cruise_id)
            ->first();


        $checkin_date = Carbon::parse($ww_cruise['dateStart']);
        $checkin_dateb = Carbon::parse($ww_cruise['dateEnd']);
        $ship = Ship::where('waterway_id', $ww_cruise['motorship']['id'])->first();

        if (!$ship) {
            ProcessLog::add("Для Водохода не найден корабль id:" . $ww_cruise['motorship']['id']);
            return;
        }


        $checkin_days = $checkin_date->diffInDays($checkin_dateb);
        $checkin_desc_1 = $this->wwGraph($ww_cruise['route']);

        $waybill = $this->wwRoutesHandler($ww_cruise['route']);

        if (!$checkin) {
            $days = $checkin_days;
            $this->daysDiffCheck($days, $ww_cruise_id);
            $checkin = new Checkin;
            $checkin->date = $checkin_date;
            $checkin->dateb = $checkin_dateb;
            $checkin->days = $days;
            $checkin->motorship_id = $ship->id;
            $checkin->active = 1;
            $checkin->desc_1 = $checkin_desc_1;
            $checkin->eds_code = 'waterway';
            $checkin->eds_id = (int)$ww_cruise_id;
            $checkin->waybill_id = $waybill;
            $checkin->save();
        } else {
            if (count($waybill) > count($checkin->waybill_id)) {
                $checkin->waybill_id = $waybill;
            } else {
                $checkin->waybill_id = 'none'; # Это значит игнорить сеттер
            }
            $checkin->date = $checkin_date;
            $checkin->dateb = $checkin_dateb;
            $checkin->desc_1 = $checkin_desc_1;
            $checkin->save();
        }

        # Запомнить время создания (или обновления) заезда
        $this->fixCheckin($checkin->id);

        foreach ($cruise_prices['result']['decks'] as $ww_deck) {
            $deck = $this->getDeck($ww_deck['name']);

            foreach ($ww_deck['roomClasses'] as $roomClass) {
                $room_category = $roomClass['name'];

                if ($this->isCabinNotLet($room_category, $ship->id)) {
                    continue;
                }

                # Получаем каюту по waterway_name
                $cabin = Cabin::where('waterway_name', $room_category)
                    ->where('motorship_id', $ship->id)
                    ->first();

                # Получаем каюту по имени
                if (!$cabin) {
                    $cabin = Cabin::where('category', $room_category)
                        ->where('motorship_id', $ship->id)
                        ->first();
                }

                # Если до этого не получили создаём новую
                if (!$cabin) {
                    $cabin = new Cabin;
                    $cabin->motorship_id = $ship->id;
                    $cabin->category = $room_category;
                    $cabin->waterway_name = $room_category;
                    $cabin->desc = $roomClass['description'];
                    $cabin->save();
                }

//                if ($ship->id === 275 || intval($ww_cruise['motorship']['id']) === 12) {
//                    file_put_contents(
//                        storage_path('waterway_275_tarrifs.json'),
//                        json_encode([
//                            'azimut_checkin_id' => $checkin ? $checkin->id : null,
//                            'waterway_checkin_id' => $ww_cruise_id,
//                            'azimut_ship_id' => $ship->id,
//                            'waterway_ship_id' => $ww_cruise['motorship']['id'],
//                            'waterway_tarrifs' => $roomClass['tariffs']
//                        ], 128 | 256),
//                        FILE_APPEND
//                    );
//                }

                foreach ($roomClass['tariffs'] as $tariff) {
                    foreach ($tariff['accommodations'] as $accommodation) {
                        $tariff_name = $accommodation['name'] ?? null;

                        # Условия вхождения тарифа
                        $tariff_entry =
                            (str_contains($tariff_name, 'Взрослый') && str_contains($tariff_name, 'завтрак'))
                            ||
                            ($tariff_name === 'Тариф взрослый' || $tariff_name === 'Тариф Взрослый');

                        if (!$tariff_entry) {
                            continue;
                        }

                        $discount_price = $accommodation['price']['discountedValue'] ?? null;
                        $price = $accommodation['price']['value'] ?? null;
                        $price = $discount_price ?? $price;

                        $places_qnt = intval($accommodation['id']); # Сколько мест

                        if (!$price) {
                            continue;
                        }

                        $price = intval(floor($price / 100));
                        $this->deckPivotCheck($cabin->id, $deck->id);
                        $this->updateCabinPrice($checkin->id, $cabin->id, $price);

                        pricePatch()->setPrice($checkin->id, $deck->id, $cabin->id, $places_qnt, $price);
                    }
                }
            }
        }
    }
}
