<?php namespace Mcmraak\Sans\Controllers;

use Mcmraak\Sans\Models\Country;
use Mcmraak\Sans\Models\Resort;
use Mcmraak\Sans\Models\Hotel;
use Mcmraak\Sans\Models\HotelCategory;
use Mcmraak\Sans\Models\Meal;
use Mcmraak\Sans\Models\Settings;

use Exception;
use Log;
use DB;


class Parser
{
    public static function parse($method, $data=null)
    {
        if($data) $data = '&'.http_build_query($data);
        $curl = curl_init();
        $api_url = "https://search-azimut:H343sd48j36@sapi.alean.ru:3443/services/json/?action=$method$data";
        #Log::debug($api_url);
        curl_setopt($curl, CURLOPT_URL, $api_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response,1);
    }

    public static function parseLists($parser)
    {
        //sleep(2); die('ok');

        if(!$parser) {
            $report = [];
            $report['Парсинг - Страны:'] = self::parseCountries();
            $report['Парсинг - Курорты:'] = self::parseResorts();
            $report['Парсинг - Категории отелей:'] = self::parseHotelCategories();
            $report['Парсинг - Отели:'] = self::parseHotels();
            $report['Парсинг - Виды питания:'] = self::parseMeals();

            return json_encode($report, JSON_UNESCAPED_UNICODE);
        } else {
            return self::{$parser}();
        }

    }

    /* Parse Countries */
    public static function parseCountries()
    {
        #sleep(1);
        #die('ok');

        $alert = '';
        $try_id = 0;
        try {
            $countries = self::parse('GetCountries');
            foreach ($countries['data'] as $item) {
                $country = Country::firstOrNew(['cid' => $item['cid']]);
                $country->id = $try_id =$item['id'];
                $country->cid = $item['cid'];
                if (!$country->name_block) $country->name = $item['name'];
                $country->save();
            }
        }

        catch (Exception $ex) {
            $alert = $ex->getMessage();
            $alert = "$try_id:  [$alert]";
        }

        if($alert) {
            return $alert;
        } else {
           return 'ok';
        }
    }

    /* Parse Resorts */
    public static function parseResorts()
    {
        #sleep(1);
        #die('ok');

        $alert = '';
        $try_id = 0;
        try {
            $resorts = self::parse('GetResorts');
            foreach ($resorts['data'] as $item) {
                $resort = Resort::firstOrNew(['cid' => $item['cid']]);
                $resort->id = $try_id = $item['id'];
                $resort->cid = $item['cid'];
                if (!$resort->name_block) $resort->name = $item['name'];
                $resort->country_id = $item['countryId'];
                $resort->save();
            }
        }
        catch (Exception $ex) {
            $alert = $ex->getMessage();
            $alert = "$try_id:  [$alert]";
        }
        if($alert) {
            return $alert;
        } else {
            return 'ok';
        }
    }

    /* Parse Hotels */
    public static function parseHotels()
    {
        #sleep(1);
        #die('ok');
        DB::table('mcmraak_sans_hotels')->update(['active' => 0]);

        $alert = '';
        $try_id = 0;
        try {
            $hotels = self::parse('GetHotels');
            foreach ($hotels['data'] as $item) {
                $hotel = Hotel::firstOrNew(['cid' => $item['cid']]);
                $hotel->id = $try_id = $item['id'];
                $hotel->name = $item['name'];
                if(isset($item['resortId']))
                    $hotel->resort_id = $item['resortId'];
                if(isset($item['hotelCategoryId']))
                    $hotel->hotel_category_id = $item['hotelCategoryId'];
                $hotel->short_name = $item['shortName'];
                $hotel->cid = $item['cid'];
                $hotel->active = 1;
                $hotel->save();
            }
            $hotel_to_delete = DB::table('mcmraak_sans_hotels')->where('active', 0)->get();
            if($hotel_to_delete->count()) {
                $cache_dir = base_path().'/storage/sans_cache/hotels/';
                foreach ($hotel_to_delete as $h) {
                    $file_path = $cache_dir.$h->id.'.gz';
                    if(file_exists($file_path)) {
                        unlink($cache_dir.$h->id.'.gz');
                    }
                }
                DB::table('mcmraak_sans_hotels')->where('active', 0)->delete();
            }


        }
        catch (Exception $ex) {
            $alert = $ex->getMessage();
            $alert = "$try_id:  [$alert]";
        }
        if($alert) {
            return $alert;
        } else {
            return 'ok';
        }
    }

    public static function parseHotelCategories()
    {
        #sleep(1);
        #die('ok');

        $alert = '';
        $try_id = 0;
        try {
            $records = self::parse('GetHotelCategories');
            foreach ($records['data'] as $item) {
                $record = HotelCategory::firstOrNew(['cid' => $item['cid']]);
                $record->id = $try_id = $item['id'];
                $record->name = $item['name'];
                if(isset($item['cid']))
                $record->cid = $item['cid'];
                $record->save();
            }
        }
        catch (Exception $ex) {
            $alert = $ex->getMessage();
            $alert = "$try_id:  [$alert]";
        }
        if($alert) {
            return $alert;
        } else {
            return 'ok';
        }
    }

    public static function parseMeals()
    {
        #sleep(1);
        #die('ok');

        $alert = '';
        $try_id = 0;
        try {
            $records = self::parse('GetMeals');
            foreach ($records['data'] as $item) {
                $record = Meal::firstOrNew(['code' => $item['code']]);
                $record->id = $try_id = $item['id'];
                $record->code = $item['code'];
                $record->save();
            }
        }
        catch (Exception $ex) {
            $alert = $ex->getMessage();
            $alert = "$try_id:  [$alert]";
        }
        if($alert) {
            return $alert;
        } else {
            return 'ok';
        }
    }

    public static function hotelsList()
    {
        return Hotel::pluck('id')->toJson();
    }



    public static function hotelProfile($hotel_id, $return_array = null, $room_id = null)
    {
        #Log::debug("start parse hotel_id:$hotel_id");

        $cache_file = base_path().'/storage/sans_cache/hotels/'.$hotel_id.'.gz';

        # Cache lifetime in minutes (is configured in the main settings)
        if(file_exists($cache_file)) {
            $cache_time = time() - filectime($cache_file) < (Settings::get('hotel_cache') * 60);
        } else {
            $cache_time = false;
        }


        $hotel_desc_cache = self::getCache($cache_file);


        if($hotel_desc_cache!==false && $cache_time){
            $hotel_desc = $hotel_desc_cache;
        } else {
            $hotel_desc = self::parse('GetHotelDescription',['id'=>$hotel_id, 'lang'=>'ru']);

            if(isset($hotel_desc['message'])){
                $hotel = Hotel::find($hotel_id);
                $hotel->bag_status = $hotel_desc['message'];
                $hotel->save();
                return $hotel_desc['message'];
            } else {
                self::putCache($cache_file, $hotel_desc);
            }

        }

        if(isset($_GET['debug']))
        {
            #Log::debug(print_r($hotel_desc,1));
            dd($hotel_desc);
        }

        if ($return_array != null and $room_id) {
               return \View::make(
                    'mcmraak.sans::room_modal',
                    [
                        'hotel' => $hotel_desc,
                        'room' => $room_id,
                        
                    ]
                );
        }

        if($return_array) return $hotel_desc;

        return 'ok';
    }

    public static function getCache($filename){

        if(!file_exists($filename)) return false;
        $data = @file_get_contents($filename);
        if(!$data) return false;
        $data = substr($data,10,-8);
        $data = gzinflate($data);
        return unserialize($data);
    }

    public static function putCache($filename, $data)
    {
        $data = serialize($data);
        $data = gzencode($data,9);
        file_put_contents($filename, $data);
    }

    public static function saveParserTime($prefix){
        $file = base_path()."/plugins/mcmraak/sans/logs/{$prefix}_time.log";
        $time = time();
        file_put_contents($file,$time);
        return date('d.m.Y H:i:s', $time);
    }
    public static function getParsersTime($prefix){
        $file = base_path()."/plugins/mcmraak/sans/logs/{$prefix}_time.log";
        if(! file_exists($file)) return;
        $time = file_get_contents($file);
        return date('d.m.Y H:i:s', $time);
    }

    public static function getInfo()
    {
        $last_parsers = self::getParsersTime('parsers');
        $last_cache = self::getParsersTime('cache');

        return 'Последняя синхронизация списков: <span class="parsers">'
            .$last_parsers.'</span>, последний прогрев кеша: <span class="cache">'
            .$last_cache.'</span>';
    }

}
