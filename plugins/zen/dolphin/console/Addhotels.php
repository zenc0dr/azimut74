<?php namespace Zen\Dolphin\Console;

use Illuminate\Console\Command;
use Zen\Cabox\Classes\Cabox;
use KubAT\PhpSimple\HtmlDomParser;
use Http;
use Zen\Dolphin\Models\City;
use Zen\Dolphin\Models\Country;
use Zen\Dolphin\Models\Hotel;
use Zen\Dolphin\Models\HotelType;
use Zen\Dolphin\Models\Region;
use System\Models\File;
use File as F;
use DB;

/*
 * Описание чего куда: https://docs.google.com/document/d/1PR5SEXYOt69uFMdwFildvwojgrJMFLhWM2uZmyhQR-M/edit
 * Документация: http://8ber.ru/s/kkj
 * Api ссылка: https://xn--e1angi.xn----7sbveuzmbgd.xn--p1ai/hotels?samo_action=hotel&HOTELINC=534
 *
 */


class Addhotels extends Command
{
    protected $name = 'dolphin:addhotels';

    protected $description = 'Добавление отелей из кеша';


    public $hotels_all, $hotel_index;
    public
        $debug_count = 0,
        $iteration,
        $csv_count = 0,
        $dom, # Спарсеный DOM страницы
        $line, # Строка в CSV-файле
        $hotel_id,
        $images_path,
        $html_string,
        $multi_lists,
        $added_hotel_count = 0,
        $samo_hotels_count = 0,
        $corrupted_ids = [];

    public function handle()
    {
        $cabox = new Cabox('dolphin.service');
        $csv_file = file(storage_path('dolphin-hotels.csv'));
        $this->csv_count = count($csv_file);
        $this->images_path = temp_path('hotel_images');
        if(!file_exists($this->images_path)) mkdir($this->images_path, 0777);

        $this->makeMultiLists();


        foreach ($csv_file as $line) {
            $this->iteration++;

            $line = trim($line);
            $line = $this->line = explode("\t", $line);
            $this->hotel_id = trim($line[0]);
            $this->html_string = $cabox->get("hotel_id:".$this->hotel_id);

            $this->addHotel();

            echo "{$this->iteration} из {$this->csv_count}\n";
        }

//        sort($this->corrupted_ids);
//        $bad_links = [];
//        foreach ($this->corrupted_ids as $hotel_id) {
//            $bad_links[] = "https://xn--e1angi.xn----7sbveuzmbgd.xn--p1ai/hotels?samo_action=hotel&HOTELINC=$hotel_id";
//        }
//
//        file_put_contents(base_path('not_parsed_samotur_hotels.txt'), join("\n", $bad_links));

        //echo "Отелей {$this->added_hotel_count} найдено {$this->samo_hotels_count}\n";
    }

    function addHotel()
    {
        $hotel_type = $this->getHotelType(); # Категория гостиницы type_id (HotelType)# Название name
        $hotel_name = $this->getHotelName(); # Название name

        if(!$hotel_name) {
            //file_put_contents(base_path('not_parsed_samotur_hotels.txt'), $this->hotel_id."\n", FILE_APPEND);
            $this->corrupted_ids[] = $this->hotel_id;
        };

        $this->added_hotel_count++;

        $db_hotel = DB::table('zen_dolphin_hotels')
            ->where('name', $hotel_name)
            ->where('created_by', 'samotur')
            ->first();

        if($db_hotel) return;


        //echo "Обработка отеля $hotel_name\n";

        $this->dom = HtmlDomParser::str_get_html($this->html_string);

        $gps = $this->getGps(); # GPS - Координаты вида lon:lat

        $address = $this->getAddress(); # Адрес

        if(!$address) {
            echo "Не найден адрес у отеля {$this->hotel_id}\n";
            file_put_contents(temp_path('no_address.txt'), "{$this->hotel_id}\n", FILE_APPEND);
            return;
        }

        $geo_ids = $this->getGeoByAddress($address);
        $note = $this->getNote(); # Примечание
        $paid_services = $this->getPaid(); # Платные услуги
        $transfer = $this->getTransfer(); # Трансфер

        $numbers_desc = $this->getNumbersDesc(); # Описние номеров

        $medical = $this->getMedical(); # Показания для лечения и профиль лечения ($medical->therapy и $medical->treatment)

        $therapy = @$medical->therapy;
        $treatment = @$medical->treatment;

        $to_sea = $this->getToSea();

        $plage_name = $this->getPlage(); # Названиея пляжа (plage_multi)
        $hops = $this->getMultiData('div.img_opisanie'); # Характеристики гостиницы (hopts_multi) -- ОПИСАНИЕ
        $services = $this->getMultiData('div.img_uslugi'); # Услуги гостиницы (hservice_multi) -- УСЛУГИ
        $hstructure = $this->getMultiData('div.img_sport'); # Инфраструктура гостиницы (hstructure_multi) -- СПОРТ

        $hotel = new Hotel;
        $hotel->name = $hotel_name;

        if($hotel_type) {
            $hotel->type_id = $this->getTypeId($hotel_type);
        } else {
            $hotel->type_id = 0;
        }

        $hotel->note = $note;
        $hotel->address = $address;
        $hotel->paid_services= $paid_services;
        $hotel->transfer = $transfer;
        $hotel->therapy = $therapy;
        $hotel->treatment = $treatment;
        $hotel->to_sea = $to_sea;
        $hotel->gps = $gps;
        $hotel->numbers_desc = $numbers_desc;

        if($geo_ids)
        {
            $hotel->country_id = $geo_ids->country_id;
            $hotel->region_id = $geo_ids->region_id;
            $hotel->city_id = $geo_ids->city_id;
        }

        $hotel->created_by = 'samotur';
        $hotel->eid = $this->hotel_id;

        $hotel->save();

        $multi_data = [$hops, $services, $hstructure];
        $multi_names = ['hopts', 'hservices', 'hstructures', 'plages'];

        $this->addMultiListsData([$hops, $services, $hstructure, [$plage_name]], $hotel);

        $images = $this->getImages();


        if($images) {
            foreach ($images as $image_path) {
                $file = (new File)->fromFile($image_path);
                $file->is_public = true;
                $file->save();
                $hotel->images()->add($file);
                echo "Добавлено изображение $image_path\n";
            }
        }


    }

    function makeMultiLists()
    {
        $table_names = [
            'hopts',
            'hservices',
            'hstructures',
            'plages'
        ];

        $multi_lists = [];

        foreach ($table_names as $table_name) {
            $records = DB::table("zen_dolphin_$table_name")->get();
            foreach ($records as $record) {
                $this->multi_lists[$table_name][] = [
                    'id' => $record->id,
                    'name' => $record->name,
                ];
            }
        }
    }

    function addMultiListsData($mixed_arrays, $hotel)
    {
        # Тут я получаю набор id из $multi_lits
        $mixed_data = [];
        foreach ($mixed_arrays as $array) {
            if(!$array || (!$array && !$mixed_data)) continue;
            $mixed_data = array_merge($mixed_data, $array);
        }

        $insert_tables = [];


        $kay_names = [
            'hopts' => 'hopt_id',
            'hservices' => 'service_id',
            'hstructures' => 'structure_id',
            'plages' => 'plage_id'
        ];

        foreach ($this->multi_lists as $table_name => $multi_list) {
            foreach ($multi_list as $item) {
                foreach ($mixed_data as $item_name) {
                    if($item['name'] == $item_name) {
                        $insert_tables[$table_name][] = $item['id'];
                    }
                }
            }
        }

        foreach ($insert_tables as $table_name => $insert_data) {
            $insert_arr = [];
            foreach ($insert_data as $id) {
                $insert_arr[] = [
                    'hotel_id' => $hotel->id,
                    $kay_names[$table_name] => $id
                ];
            }
            DB::table("zen_dolphin_{$table_name}_pivot")->insert($insert_arr);
        }

    }

    function addMultiData($names, $model_table, $pivot_table, $hotel_id, $key)
    {
        if(!$names) return;
        if(!is_array($names)) dd($names, $model_table);

        $prefix = 'zen_dolphin_';
        $model_records = DB::table($prefix.$model_table)->get();

        foreach ($model_records as $model_record) {
            foreach ($names as $name) {
                if($name == $model_record->name) {
                    $is_exist = DB::table($prefix.$pivot_table)
                        ->where('hotel_id', $hotel_id)
                        ->where($key, $model_record->id)
                    ->first();
                    if(!$is_exist) {
                        DB::table($prefix.$pivot_table)->insert([
                            'hotel_id' => $hotel_id,
                            $key => $model_record->id
                        ]);
                    }
                }
            }
        }
    }

    function getCacheUrl()
    {
        echo "http://azimut.dc/zen/dolphin/api/Debug@viewHotelPage?id={$this->hotel_id}\n";
        die;
    }

    /**
     * @param $begin - Вхождение до строки
     * @param $end - Вхождение после строки
     * @return string
     */
    function contentParser($begin, $end)
    {
        $a = explode($begin, $this->html_string);
        if(!isset($a[1])) return false;
        $b = explode($end, $a[1]);
        if(!isset($b[0])) return false;
        return trim($b[0]);
    }

    function getUlData($node)
    {
        $items = $node->find('li');
        if(!$items) return;

        $items = $node->find('li');
        $output = [];
        foreach ($items as $item) {
            $text = trim($item->text());
            $text = preg_replace('/ &#8211;.*$/', '', $text);
            $output[] = $text;
        }
        return $output;
    }

    function getMultiData($selector)
    {
        $node = $this->dom->find($selector, 0);
        if(!$node) return;

        $ul_data = $this->getUlData($node->parent()->next_sibling());
        if($ul_data) return $ul_data;

        $text = $node->parent()->next_sibling()->text();
        $text = str_replace("\t", '', $text);
        $text = trim($text);
        $text = preg_replace('/ {2,}/', '', $text);
        $text = preg_replace('/ &#8211;.*$/', '', $text);
        $text = str_replace(".", '', $text);
        $text = str_replace(",", '|', $text);
        return explode('|', $text);
    }

    function getHotelType()
    {
        $hotel_type = $this->contentParser('<div class="starname" style="color:#222;">', '</div>', $this->html_string);
        if(strpos($hotel_type, '<span') !== false) return;
        return $hotel_type;
    }

    function getHotelName()
    {
        return $this->contentParser('<div class="hotellname">', '</div>', $this->html_string);
    }

    public $list_tags = [];

    function addListTags($tags)
    {
        if(!$tags) return;
        $tags = explode('|', $tags);
        $this->list_tags = array_merge($this->list_tags, $tags);
        $this->list_tags = array_unique($this->list_tags);
    }


    function getAddress()
    {
        # https://github.com/sunra/php-simple-html-dom-parser <-- Не совместим с php 7.3
        # https://github.com/Kub-AT/php-simple-html-dom-parser <-- Установил этот !!!

        # https://github.com/FriendsOfPHP/Goutte <-- Этот рекомендуют, но я его ещё не смотрел
        # https://symfony.com/doc/current/components/dom_crawler.html <-- Вообще этот правильный

        # https://simplehtmldom.sourceforge.io/manual.htm#section_traverse <-- описание синтаксиса

        $node = $this->dom->find('td[class=title]', 0);

        if(!$node) return false;

        return $node->next_sibling()->text();
    }

    function getGps()
    {
        $lon =  @$this->line[3]; # Долгота N
        $lat =  @$this->line[4]; # Широта E
        if(!$lon || !$lat) return;

        if(mb_strlen($lon) < 9) $lon .= str_repeat('0', 9 - mb_strlen($lon));
        if(mb_strlen($lat) < 9) $lat .= str_repeat('0', 9 - mb_strlen($lat));

        return str_replace(',', '.', "$lon:$lat");
    }

    # Получить примечание
    function getNote()
    {
        return $this->dom->find('div[class=hotel_layout panel]', 0)->find('div', 0)->text();
    }

    # Платные услуги
    function getPaid()
    {
        $node = $this->dom->find('div.img_platno', 0);
        if(!$node) return;

        $text = trim($node->parent()->next_sibling()->text());
        $text = str_replace('Платные услуги &#8211; ', '', $text);
        return $text;
    }

    function getNumbersDesc()
    {
        $node = $this->dom->find('div.img_dopolnitelyno', 0);
        if(!$node) return;
        if(strpos($node->parent()->next_sibling()->outertext, 'Описание номеров') !== false) {
            return $node->parent()->next_sibling()->find('.content', 0)->outertext;
        }
    }

    # Трансфер
    function getTransfer()
    {
        if(strpos($this->html_string, 'Трансфер') === false) return;

        $titles = $this->dom->find('td.title');

        foreach ($titles as $title) {
            if($title->text() == 'Трансфер:') {

                $text = $title->next_sibling()->outertext;
                $text = preg_replace('/<[a-z\/ ]+>/', '', $text);
                $text = preg_replace('/ {2,}/', '', $text);

                return $text;
            }
        }
    }

    # Показания для лечения и Профиль лечения
    function getMedical()
    {
        if(strpos($this->html_string, 'Показания для лечения') === false && strpos($this->html_string, 'Профиль лечения') === false) return;
        $nodes = $this->dom->find('div.img_lechenie', 0)->parent()->next_sibling()->find('li');

        $therapy = null;
        $treatment = null;

        foreach ($nodes as $node) {
            if(strpos(@$node->outertext, 'Показания для лечения')) {
                $therapy = $node->find('span.content', 0)->text();
            }
            if(strpos(@$node->outertext, 'Профиль лечения')) {
                $treatment = $node->find('span.content', 0)->text();
            }
        }

        return (object) [
            'therapy' => $therapy,
            'treatment' => $treatment
        ];

    }

    # Расстояние до моря в метрах
    function getToSea()
    {
        if(strpos($this->html_string, 'до моря') === false) return 0;
        $node = $this->dom->find('div.img_rasstoyanie', 0);
        if(!$node) return 0;
        return intval(
            $node->parent()
            ->next_sibling('p.content', 0)
            ->find('span.content', 0)
            ->text()
        );
    }

    # Пляж
    function getPlage()
    {
        if(strpos($this->html_string, 'Пляж') === false) return;
        $node = $this->dom->find('div.img_plyazh', 0);

        if(!$node) return;

        $text = $node->parent()->next_sibling()->text();

        $text = str_replace('.', '', $text);
        return trim($text);
    }

    function getImages()
    {
        $nodes = $this->dom->find('a.thumbnail');
        $urls = [];

        if(!$nodes) return;
        foreach ($nodes as $node) {
            $urls[] = $node->href;
        }

        foreach ($urls as $url) {
            $temp_path = str_replace('/data/hotel', $this->images_path, $url);
            if(!file_exists($temp_path)) {
                $image_url = 'https://xn--e1angi.xn----7sbveuzmbgd.xn--p1ai'.$url;
                echo "Скачиваю изображение $image_url :";

                $image = $this->curlQuery($image_url);


                file_put_contents($temp_path, $image);
                echo "ok\n";
            }
        }

        foreach ($urls as &$url) {
            $url = str_replace('/data/hotel', $this->images_path, $url);
            if(!file_exists($url)) {
                echo "Изображение $url отсутствует!!!\n";
                die;
            };
        }

        return $urls;
    }

    function getGeoByAddress($address)
    {
        if(!$address) return;

        $cut_words = [
            'Республика',
            'пр-д.',
            'пос.',
            'п.',
            'г.',
            'мкр.',
            'район',
            'с.',
        ];

        $exclude_words = [
            'д.',
            'ст.',
            'ул.',
            'пер.'
        ];

        $exclude_regexp = [
            "/^\d+$/",
            "/^д\.\d+$/",
            "/^\d+\D$/"
        ];


        $address_slots = explode(',', $address);


        $country_id = 0;
        $region_id = 0;
        $city_id = 0;

        $country_name = null;
        $region_name = null;
        $city_name = null;

        foreach ($address_slots as $address_slot)  {
            $address_slot = str_replace($cut_words, '', $address_slot);
            $address_slot = trim($address_slot);

            $stop = false;

            foreach ($exclude_words as $exclude_word) {
                if(strpos($address_slot, $exclude_word) !== false) {
                    $stop = true;
                    break;
                }
            }

            if($stop) continue;

            $stop = false;
            foreach ($exclude_regexp as $regex) {
                if(preg_match($regex, $address_slot)) {
                    $stop = true;
                    break;
                }
            }

            if($stop) continue;

            if(!$country_id) {
                $country = Country::where('name', $address_slot)->first();
                if($country) {
                    $country_id = $country->id;
                }
            }

            if(!$region_id) {
                $region = Region::where('name', $address_slot)->first();
                if($region) {
                    $region_id = $region->id;
                }
            }

            if(!$city_id) {
                $city = City::where('name', $address_slot)->first();
                if($city) {
                    $city_id = $city->id;
                }
            }


            if($city_id) {
                if($city->region_id && $region_id != $city->region_id) {
                    $region_id = $city->region_id;
                    $region = Region::find($region_id);
                    if($region->country_id && $country_id != $region->country_id) {
                        $country_id = $region->country_id;
                    }
                }
            }

            if(!$city_id && $region_id) {
                if($region->country_id && $country_id != $region->country_id) {
                    $country_id = $region->country_id;
                }
            }

        }

        return (object) [
            'country_id' => $country_id,
            'region_id' => $region_id,
            'city_id' => $city_id
        ];

        #return "<tr><td>$address</td><td>$country_name</td><td>$region_name</td><td>$city_name</td></tr>";

    }

    function getTypeId($hotel_type)
    {
        $type = HotelType::where('name', $hotel_type)->first();
        if($type) return $type->id;

        $type = new HotelType();
        $type->name = $hotel_type;
        $type->save();
    }


    function curlQuery($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch); curl_close($ch);
        return $response;
    }

}
