<?php namespace Zen\Worker\Pools;


use Carbon\Carbon;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Classes\Getter;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Exception;
use Zen\Worker\Classes\Http;
use DB;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Classes\Search;

class RiverCrs
{
    private array $cache = [];


    public function riverQuery($url, $format = 'json', $data = null, $key = null)
    {
        if ($key) {
            $response = $this->stream->cache->get($key);
            if ($response) {
                return $response;
            }
        }

        $http = new Http;
        $http_query = $http->setTimout($this->pool_state->timeout);
        if ($data) {
            $http_query->dataGet($data);
        }
        $http_query->query($url, $format);

        if ($http_query->error) {
            throw new Exception($http_query->error);
        }
        $response = $http_query->response;

        if ($key) {
            $this->stream->cache->put($key, $response);
        }

        return $response;
    }

    public function riverQueryPost($url, $format = 'json', $data = null, $json_query = false, $key = null)
    {
        if ($key) {
            $response = $this->stream->cache->get($key);
            if ($response) {
                return $response;
            }
        }

        $http = new Http;
        $http_query = $http->setTimout(300);
        if ($json_query) {
            $http_query->jsonPost($data);
        } else {
            $http_query->dataPost($data);
        }
        $http_query->query($url, $format);

        if ($http_query->error) {
            throw new Exception($http_query->error);
        }
        $response = $http_query->response;

        if ($key) {
            $this->stream->cache->put($key, $response);
        }

        return $response;
    }

    public function getDeck($deck_name)
    {
        $getter = new Getter;
        return $getter->getDeck($deck_name);
    }

    public function updateCabinPrice(
        $checkin_id,
        $cabin_id,
        $price_value,
        $price2_value = null,
        $get_queries_string = false
    ) {
        $getter = new Getter;
        return $getter->updateCabinPrice($checkin_id, $cabin_id, $price_value, $price2_value, $get_queries_string);
    }

    public function deckPivotCheck($cabin_id, $deck_id)
    {
        $getter = new Getter;
        $getter->deckPivotCheck($cabin_id, $deck_id);
    }

    /**
     * Тут происходит создание категории каюты
     * @param string $category_name
     * @param int $motorship_id
     * @param string $eds_code
     * @param int $places
     * @return int
     */
    public function getCabinCategoryId(
        string $category_name,
        int $motorship_id,
        string $eds_code,
        int $places = 1,
        string $eds_id = null
    ): int {
        # Супер-костыль специально для Гама
        if ($eds_code === 'gama') {
            $category_name = "$category_name|$eds_id";
        }

        $key = "cabin:$category_name:$motorship_id:$eds_code";

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $cabin = Cabin::where($eds_code . '_name', $category_name)
            ->where('motorship_id', $motorship_id)
            ->first();

        if ($cabin) {
            if ($cabin->places_main_count !== $places) {
                $cabin->places_main_count = $places;
                $cabin->save();
            }
            return $cabin->id;
        }

        $cabin = Cabin::where('category', $category_name)
            ->where('motorship_id', $motorship_id)
            ->first();

        if ($cabin) {
            return $cabin->id;
        }

        $cabin = new Cabin;
        $cabin->motorship_id = $motorship_id;
        $cabin->category = $category_name;
        $cabin->{$eds_code . '_name'} = $category_name;
        $cabin->places_main_count = $places;
        $cabin->desc = '';
        $cabin->save();

        return $this->cache[$key] = $cabin->id;
    }

    public function isCabinNotLet($cabin_name, $motorship_id, $not_let = null)
    {
        $getter = new Getter;
        return $getter->isCabinNotLet($cabin_name, $motorship_id, $not_let);
    }

    public function getTownId($name, $eds_code = null)
    {
        if (isset($this->cache["town:$name:$eds_code"])) {
            return $this->cache["town:$name:$eds_code"];
        }
        $getter = new Getter;
        return $this->cache["town:$name:$eds_code"] = $getter->getTownId($name, $eds_code);
    }

    public function checkSeparator($string = null)
    {
        $getter = new Getter;
        return $getter->checkSeparator($string);
    }

    public function getShortWaybillIds($string): array
    {
        $key = md5($string);

        if (isset($this->cache["wb:$key"])) {
            return $this->cache["wb:$key"];
        }

        $string = $this->checkSeparator($string);
        $array = explode('-', $string);
        $ids = [];
        foreach ($array as $value) {
            $value = trim($value);
            $value = str_replace('⏹', '-', $value);
            $ids[] = $this->getTownId($value);
        }
        return $this->cache["wb:$key"] = $ids;
    }

    public function updateCabinPriceQueries($del_q, $ins_q)
    {
        $getter = new Getter;
        $getter->updateCabinPriceQueries($del_q, $ins_q);
    }

    public function getMotorship($name, $eds_field, $eds_id, $desc = '')
    {
        if (CacheSettings::shipIsBad($name, $eds_field)) {
            return false;
        }

        if (strpos($eds_field, '_id') === false) {
            $eds_field .= '_id';
        }

        $ship = Ship::where($eds_field, $eds_id)->first();
        if ($ship) {
            return $ship;
        }

        $ship = Ship::where('name', 'like', "%$name%")->first();

        if ($ship) {
            $ship->{$eds_field} = $eds_id;
            $ship->save();
            return $ship;
        }

        $ship = new Ship;
        $ship->name = $name;
        $ship->desc = $desc;
        $ship->add_a = '';
        $ship->add_b = '';
        $ship->booking_discounts = '';
        $ship->social_discounts = '';
        $ship->youtube = '';
        $ship->banner = '';
        $ship->techs = [];
        $ship->{$eds_field} = $eds_id;
        $ship->save();
        return $ship;
    }

    public function daysDiffCheck($diff, $id)
    {
        if ($diff < CacheSettings::get('days_diff')) {
            $message = "Заезд:$id Не соответствует условию (Время заезда в днях = $diff)";
            throw new Exception($message);
        }
    }

    public function diffInIncompliteDays($date_a, $date_b)
    {
        $date_a = substr($date_a, 0, 11) . '00:00:00';
        $date_b = substr($date_b, 0, 11) . '00:00:00';
        $start = Carbon::parse($date_a);
        $end = Carbon::parse($date_b);
        $diff = $end->diffInDays($start);
        $diff++;
        return $diff;
    }

    public function fixCheckin($checkin_id)
    {
        $t = 'mcmraak_rivercrs_checkins_memory';
        $checkin = DB::table($t)->where('checkin_id', $checkin_id)->first();
        $now = date('Y-m-d h:i:s');
        if (!$checkin) {
            DB::table($t)->insert([
                'checkin_id' => $checkin_id,
                'updated_at' => $now
            ]);
        } else {
            DB::table($t)->where('checkin_id', $checkin_id)->update([
                'updated_at' => $now
            ]);
        }
    }


    public function removeNotActualCheckins($eds_code)
    {
        $exist_ids = DB::table('mcmraak_rivercrs_checkins')
            ->where('eds_code', $eds_code)
            ->select('id')
            ->pluck('id')->toArray();

        $memory_ids = DB::table('mcmraak_rivercrs_checkins_memory')
            ->select('checkin_id')
            ->pluck('checkin_id')->toArray();

        $allowed_ids = array_intersect($exist_ids, $memory_ids);
        $removed_ids = array_diff($exist_ids, $allowed_ids);

        foreach ($removed_ids as $id) {
            $this->stream->addJob('Service@removeCheckin', ['id' => (int) $id]);
        }
    }

    public function renderCheckin($checkin, $opts = [])
    {
        $search = new Search;
        $search->renderCheckin($checkin, $opts);
    }
}
