<?php namespace Zen\Dolphin\Parsers;

use Zen\Dolphin\Models\Hotel;
use Zen\Dolphin\Classes\Parser;
use Zen\Dolphin\Models\Country;
use Zen\Dolphin\Models\Region;
use Zen\Dolphin\Models\City;
use Zen\Dolphin\Models\HotelType;

/*  ### Идентификаторы активных отелей + описания отелей
 *  Документация: https://delfinota.docs.apiary.io/#reference/0/hotelsidsnewtourtypeid
 *  Документация: https://delfinota.docs.apiary.io/#reference/0/hotelcontentidid
 *  tour_type=0,12,20,53,56,57,58,64
 *  tour_type=0,12,20,53,64 <-- Пока выбраны только эти, в дальнейшем todo: вывести в настройки
 *  0 - Оздоровление, 12 - Отдых
 *  20 - Бронирование отелей (Москва, С.Петербург)
 *  53 - Лечение, 56 - Рыболовные туры
 *  57 - Активный отдых, 58 - Школьные туры
 *  64 - Городские отели, 5 - Экскурсия
 */

class HotelContent extends Parser
{
    private $dolphin, $attempts = 0;

    function go()
    {
        $this->parser_class_name = 'HotelContent';
        $this->parser_name = 'Гостиницы';

        $this->fillHotelTypesMemory();

        $this->saveProgress('Получение идентификаторов...');
        $this->dolphin = $this->store('Dolphin');
        $hotel_ids = $this->dolphin->attemps(5, 5, 15)->query('HotelsIdsNew?tour_type=0,5,12,20,53,64');

        $this->initCounts(count($hotel_ids));
        foreach ($hotel_ids as $hotel_id) {
            $this->attempts = 5;
            $hotel_content = $this->getHotelContent($hotel_id);
            if(!$hotel_content) {
                $this->log([
                    'source' => 'Parsers > HotelContent > getHotelContent',
                    'text' => 'Источник не вернул данные',
                    'dump' => [
                        'dolphin_hotel_id' => $hotel_id,
                    ]
                ]);
                $this->saveProgress();
                continue;
            }
            $this->addHotel($hotel_content);
            $this->saveProgress();
        }

        $this->parserSuccess();
    }

    function getHotelContent($hotel_id)
    {
        if(!$this->attempts) return false;
        $hotel_content = $this->dolphin->attemps(5, 5, 15)->query("HotelContent?id=$hotel_id", ['cache_key' => "dolpin.hotel.id#$hotel_id"]);
        if($hotel_content) return $hotel_content;
        $this->attempts--;
        sleep(3);
        return $this->getHotelContent($hotel_id);
    }

    function addHotel($hotel_content)
    {
        $eid = $hotel_content['Id'];
        $hotel = Hotel::where('eid', $eid)->first();

        $new_hotel = false;

        if(!$hotel) {
            $hotel = new Hotel;
            $new_hotel = true;
        }

        $hotel->eid = $eid;

        if($new_hotel) $hotel->name = $hotel_content['Name'];
        $hotel->stars = $hotel_content['Stars'];
        $hotel->type_id = $this->getHotelTypeId($hotel_content['Type']);
        $hotel->contacts = $hotel_content['Contacts'];
        $hotel->address = $hotel_content['Adress'];
        $hotel->gps = $hotel_content['Longitude'].':'.$hotel_content['Latitude'];
        $hotel->country_id = $this->getGeoObject($hotel_content['Country'], Country::class);
        $hotel->region_id = $this->getGeoObject($hotel_content['Region'], Region::class);
        $hotel->city_id = $this->getGeoObject($hotel_content['City'], City::class);
        $hotel->distinct = $hotel_content['Distinct'];
        $hotel->checkout_time = $hotel_content['CheckoutTime'];
        $hotel->checkin_time = $hotel_content['CheckinTime'];
        if($new_hotel) $hotel->info = $hotel_content['Info'];
        if($new_hotel) $hotel->ci = $hotel_content['ConstructionInfo'];
        if($new_hotel) $hotel->ta = $hotel_content['TransportAccessability'];
        $hotel->created_by = 'dolphin';
        if(isset($hotel_content['Photos1020x700'])) {
            $hotel->gallery = $hotel_content['Photos1020x700'];
        }
        $hotel->save();
    }

    function getGeoObject($eid, $model)
    {
        $record = $model::where('eid', $eid)->first();
        if(!$record) return 0;
        return $record->id;
    }

    private $hotel_types_memory = [];

    function fillHotelTypesMemory()
    {
        $hotel_types = HotelType::get();
        foreach ($hotel_types as $hotel_type) {
            $this->hotel_types_memory[$hotel_type->name] = $hotel_type->id;
        }
    }

    function getHotelTypeId($name)
    {
        if(isset($this->hotel_types_memory[$name])) return $this->hotel_types_memory[$name];
        $hotel_type = HotelType::where('name', $name)->first();
        if(!$hotel_type) {
            $hotel_type = new HotelType;
            $hotel_type->name = $name;
            $hotel_type->created_by = 'dolphin';
            $hotel_type->save();
        }

        $this->hotel_types_memory[$name] = $hotel_type->id;
        return $hotel_type->id;
    }

}
