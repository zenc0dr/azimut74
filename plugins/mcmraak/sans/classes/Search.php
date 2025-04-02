<?php namespace Mcmraak\Sans\Classes;

use Mcmraak\Sans\Controllers\Parser;
use Mcmraak\Sans\Models\Hotel;
use Mcmraak\Sans\Models\Meal;
use Mcmraak\Sans\Models\HotelCategory;
use Mcmraak\Sans\Models\Group;
use Mcmraak\Sans\Models\Resort;
use Mcmraak\Sans\Models\Settings;
use Twig;
use Log;
use Session;

class Search
{
/*
[resort_id] => 492
[adults] => 1
[kids] => 3
[kidsAges] => Array
    (
        [0] => 5
        [1] => 5
        [2] => 10
    )

[dateFrom] => 12.12.2017
[dateTo] => 22.12.2017
[nightsMin] => 2
[nightsMax] => 5
[serach_by_hotel_name] => Kazzhol
*/

    public function test()
    {
        $hotel_names = 'Лада';
        $hotels_ids = Hotel::where('name',  'like', "%$hotel_names%")
            ->whereNull('bag_status')
            ->pluck('id')
            ->toArray();
        dd($hotels_ids);
    }


    public static $perPage = 15;

    public static function searchResult($data=null)
    {

        #Log::debug(print_r($data,1));

        # Save query dump
        #file_put_contents(temp_path().'/sans_query_dump.txt', serialize($data));

        # Load query dump (http://azimut.dc/sans/api/v1/search/query)
        #$data = unserialize(file_get_contents(temp_path().'/sans_query_dump.txt'));
        #dd($data);

        $original_filter_data = $data;

        $page = (isset($data['page'])) ? $data['page'] : 1;
        $second_filter = (isset($data['second_filter'])) ? $data['second_filter'] : null;
        unset($data['page']);
        unset($data['second_filter']);
        Session::put('sans_query_data', $data);

        $query_key = md5(serialize($data));
        $cache_file = base_path().'/storage/sans_cache/queries/'.$query_key.'.gz';
        $query_file = base_path().'/storage/sans_cache/queries/'.$query_key.'.query';

        if(file_exists($cache_file)) {
            $cache_time = time() - filectime($cache_file) < (Settings::get('search_cache') * 60);
        } else {
            $cache_time = false;
        }

        if(!$cache_time) {

            if (@$data['search_by_hotel_name']) {
                $hotel_names = $data['search_by_hotel_name'];
                $hotels_ids = Hotel::where('name',  'like', "%$hotel_names%")->whereNull('bag_status')->pluck('id')->toArray();
                $hotels_ids = join(',', $hotels_ids);
            }

            if (!$data['dateFrom'] ?? false) {
                return 'no_results';
            }

            $sendData = [];
            $sendData['countryId'] = 1;
            $sendData['dateFrom'] = $data['dateFrom'];
            $sendData['dateTo'] = $data['dateTo'];
            $sendData['count'] = Settings::get('results'); // Максимальное количество предложений в выдаче
            $sendData['adults'] = $data['adults']; // Количество взрослых
            $sendData['kids'] = $data['kids']; // Количество детей
            if ($data['kids']) {
                $sendData['kidsAges'] = $data['kidsAges']; // Возрасты детей
            }
            $sendData['nightsMin'] = $data['nightsMin']; // Дней минимум
            $sendData['nightsMax'] = $data['nightsMax']; // Дней максимум
            $sendData['resorts'] = self::getResortsIds($data['resort_id']); // Курорт
            #$sendData['hotelCategories'] = 0; // Категория отеля

            if ($data['search_by_hotel_name'])
                $sendData['hotels'] = $hotels_ids; // Отели (идентификаторы)


            #$sendData['priceMin'] = 0; // Минимальная цена в рублях
            #$sendData['priceMax'] = 0; // Максимальная цена в рублях
            $sendData['currencyId'] = 1; // Валюта 1 = RUB
            $sendData['lang'] = 'ru';


            $result = Parser::parse('GetTours', $sendData);

            if(isset($result['message'])){
                return 'no_results';
            }


            self::logArray($result,'results');

            $return = self::resultHandler($result);

            Parser::putCache($cache_file, $return);
            file_put_contents($query_file, serialize($data));
        } else {
            $return = Parser::getCache($cache_file);
        }

        #Log::debug(print_r($return,1));
        #Log::debug(print_r($second_filter,1));

        $filtred = self::secondFilter($return, $second_filter);

        if ($data['typeSearch'] == 'room') {

            //Log::debug(print_r($filtred,1));

            #$hotel = Hotel::find($filtred[0]['hotel_id']);
            $results = Parser::getCache($cache_file);

            $toHtml = (string) \View::make('mcmraak.sans::results_room',
            [
                'results' => $results,
                'hash' => self::hotelFilter($filtred[0]['hotel_id'], $results),
                'query_key' => $query_key
            ]);

            return json_encode ([
                'json' => $return,
                'html' => $toHtml,
            ], JSON_UNESCAPED_UNICODE);
        }

        $toHtml = (string) \View::make('mcmraak.sans::results',
            [
                'results' => $filtred,
                'pagination' => self::pagination($filtred, $page),
                'query_key' => $query_key,
                'price_prefix' => self::getPricePrefixInfo($original_filter_data),
                'self' => new self,
            ]);

        return json_encode ([
            'json' => $return,
            'html' => $toHtml,
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function getPricePrefixInfo($filter_data)
    {
        $adults = intval($filter_data['adults']);
        $kids = intval($filter_data['kids']);
        $kidsAges = $filter_data['kidsAges'];

        $kids_word = '';
        if($kidsAges) {
            $kidsAges = explode(',', $kidsAges);
            $last_age = $kidsAges[count($kidsAges)-1];
            $age_word = self::incline(['год','года','лет'], $last_age);
            $kids_word = ", $kids реб (". join(', ',$kidsAges) ." $age_word)";
        }

        $return = "Цена на $adults взр$kids_word";

        return $return;
    }

    public static function inc_nights($nights)
    {
        return self::incline(['ночь','ночи','ночей'], intval($nights));
    }

    public static function incline($words, $n){
        if($n%100>4 && $n%100<20){
            return $words[2];
        }
        $a = [2,0,1,1,1,2];
        return $words[$a[min($n%10,5)]];
    }


    public static function hotelFilter($hotel_id, $results){

        $filtered = [];
        foreach ($results as $result)
        {
            if($result['hotel_id'] == $hotel_id){
                $result['hash'] = self::resultToHash($result);
                $filtered[] = $result;
            }
        }
        return $filtered;
    }

    public static function resultToHash($result)
    {
        return md5(
            $result['tour_name'].
            $result['room_type'].
            $result['nights'].
            $result['meal'].
            $result['price']
        );
    }







    public static function getResortsIds($resort)
    {
        if(preg_match('/^group_id:/',$resort)){
            $group_id = str_replace('group_id:', '', $resort);
            $group = Group::find($group_id);
            $groups_ids = $group->allChildren()->get()->pluck('id')->toArray();
            $groups_ids[] = $group->id;

            $resorts_ids = Resort::whereIn('group_id', $groups_ids)->get()->pluck('id')->toArray();
            return join(',', $resorts_ids);
        } else {
            return $resort;
        }
    }

    public static function pagination($records, $page)
    {
        $total = count($records);
        return [
            'lastPage' => ceil(count($records) / self::$perPage),
            'total' => $total,
            'perPage' => self::$perPage,
            'currentPage' => $page
        ];
    }

    public static function resultHandler($result)
    {
        /* Meal codes */
        #var_dump($result);

        $meals = self::getMealsArr();
        $hotelCategories = self::getHotelCategories();

        #file_put_contents(base_path().'/plugins/mcmraak/sans/docs/result.txt',serialize($result));
        #$result = file_get_contents(base_path().'/plugins/mcmraak/sans/docs/result.txt');
        #$result = unserialize($result);
        #dd($result);

        #self::logArray($result,'hotels');


        $return = [];

        foreach ($result['data'] as $record){

            $hotel_id = $record['hotelId'];

            $hotel_object = Hotel::find($hotel_id);

            if(!$hotel_object || $hotel_object->bag_status) continue;

            $hotel = $hotel_object->bag; # Описание отеля

            $hotelCategory = 'Не указана';

            if($hotel_object->hotel_category_id){
                $hotelCategory = $hotelCategories['by_id'][$hotel_object->hotel_category_id];
            }

            if(!$hotelCategory)
            if(isset($record['hotelCategoryId'])){
                $hotelCategory = $hotelCategories['by_id'][$record['hotelCategoryId']];
            }

            if(!$hotelCategory)
            if(isset($hotel['HotelCategoryCID'])) {
                $hotelCategory = $hotelCategories['by_cid'][$hotel['HotelCategoryCID']];
            }

            #Log::debug('hotel_id:'.$hotel_id);

           // Log::debug(print_r($hotel,1));

            $return[] = [
                'hotel_id' => $hotel_id,
                'tour_name' => $record['tourName'],
                'tour_date' => $record['tourDate'],
                'hotel_type' => $hotel['HotelTypeName'] ?? '',
                'hotel_name' => $hotel['HotelName'],
				'town_name' => $hotel['TownName'],
				'area_name' => (isset($hotel['AreaName'])) ? $hotel['AreaName']:'',
                'сountry_name' => $hotel['CountryName'],
                'hotel_image' => (isset($hotel['HotelImageList']))?$hotel['HotelImageList'][0]['Url']:'',
                'room_type' => $record['roomTypeName'],
                'nights' => $record['nights'],
                'meal' => $meals[$record['mealCode']],
                'price' => $record['price'],
                'level' => $hotelCategory,
                'desc' => (isset($record['description'])) ? $record['description'] : false,
                'medical' => self::medicalHandler($hotel),
                'pool' => self::poolSearch($hotel),
                'address' => (isset($hotel['Address'])) ? $hotel['Address'] : false,
                'coordinates' => (isset($hotel['Coordinates'])) ? $hotel['Coordinates'] : '',
                'sea_distance' => self::seaDistance($hotel),
            ];
        }

        #dd($return);
        return $return;
    }

    public static function getHotelCategories()
    {
        $collection = HotelCategory::get();

        $by_id = [];
        foreach($collection as $item)
        {
            $by_id[$item->id] = $item->name;
        }

        $by_cid = [];
        foreach($collection as $item)
        {
            $by_cid[$item->cid] = $item->name;
        }

        return [
            'by_id' => $by_id,
            'by_cid' => $by_cid,
        ];
    }

    public static function getMealsArr()
    {
        $collection = Meal::get();
        $return = [];
        foreach($collection as $item)
        {
            $return[$item->code] = $item->name;
        }
        return $return;
    }

    public static function medicalHandler($hotel)
    {
        if(!isset($hotel['MedicalService'])) return false;

        #if(!isset($hotel['MedicalService']['MedicalProfileList'])) return false;

        if(isset($hotel['MedicalService']['MedicalProfileList'])){
            $MedicalService = $hotel['MedicalService']['MedicalProfileList'];
            $return = [];
            foreach ($MedicalService as $item){
                $return[] = $item['Name'];
            }
            return $return;
        } else {
            return ['-- Прочее --'];
        }
    }



    public static function poolSearch($hotel)
    {
        if(!isset($hotel['InfrastructureObjectList'])) return false;
        $is = $hotel['InfrastructureObjectList'];

        foreach ($is as $item){
            if(preg_match('/бассейн/ui', $item['Name'])){
                return true;
            }
        }

        return false;
    }

    public static function seaDistance($hotel)
    {
        if(!isset($hotel['DistanceList'])) return false;

        $distances = $hotel['DistanceList'];

        foreach ($distances as $item){
            if(preg_match('/до моря/ui', $item['Name'])){
                return $item['Distance'];
            }
        }

        return false;
    }

    public static function uniqueHotels($results){

        #self::logArray($results, 'results_before');

        $uniqueTable = [];

        $key = 0;
        foreach ($results as $result)
        {
            $hotel_id = $result['hotel_id'];
            $price = $result['price'];

            if(isset($uniqueTable[$hotel_id])){
                if($price < $uniqueTable[$hotel_id]['price']) {
                    $uniqueTable[$hotel_id] = [
                        'price' => $price,
                        'key' => $key,
                    ];
                }
            } else {
                $uniqueTable[$hotel_id] = [
                    'price' => $price,
                    'key' => $key,
                ];
            }
            $key++;
        }

        $uniqueResults = [];

        foreach ($uniqueTable as $item)
        {
            $uniqueResults[] = $results[$item['key']];
        }


        //self::logArray($uniqueTable, 'unique_table');


        return $uniqueResults;
    }

    public static function secondFilter($results, $filter){

        /* Unique Hotels Filter */
        $results = self::uniqueHotels($results);

        #self::logArray($filter, 'filter');
        #self::logArray($results, 'results_before');

        $filtered = [];

        foreach ($results as $result)
        {
            $permit = true;

            /* Price */
            if($result['price'] < $filter['price_from']) $permit = false;

            if($filter['price_to'])
            if($result['price'] > $filter['price_to']) $permit = false;


            /* Level */
            if(
                $filter['hotel_levels']['selected'] &&
                $result['level'] != $filter['hotel_levels']['selected']
            ) $permit = false;


            /* Hotel type */
            if(
                $filter['hotel_types']['selected'] &&
                $result['hotel_type'] != $filter['hotel_types']['selected']
            ) $permit = false;


            /* Meal */
            if(
                $filter['meals']['selected'] &&
                $result['meal'] != $filter['meals']['selected']
            ) $permit = false;

            /* Pool */
            if($filter['pool'] == 'true' && !$result['pool']) $permit = false;

            /* Medical */
            if($permit && $filter['medical'] == 'true')
            {
                if(isset($result['medical']))
                {
                    if($result['medical'])
                    {
                        if(isset($filter['medical_selected']))
                        {
                            foreach ($result['medical'] as $rm){
                                if(in_array($rm, $filter['medical_selected'])) {
                                    $permit = true;
                                    break;
                                } else {
                                    $permit = false;
                                }
                            }
                        }
                    } else {
                        $permit = false;
                    }
                } else {
                    $permit = false;
                }
            }

            if($permit) $filtered[] = $result;
        }


        #self::logArray($filtered, 'results_after');
        return $filtered;
    }

    public static function logArray($array, $filename)
    {
        return; # comment to enable
        file_put_contents(base_path().'/plugins/mcmraak/sans/logs/'.$filename, serialize($array));
    }

    public static function dumpArray($filename)
    {
        $array = file_get_contents(base_path().'/plugins/mcmraak/sans/logs/'.$filename);
        $array = unserialize($array);
        dd($array);
    }

}
