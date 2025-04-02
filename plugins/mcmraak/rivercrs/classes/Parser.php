<?php namespace Mcmraak\Rivercrs\Classes;

use Input;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Log;
use Mcmraak\Rivercrs\Models\Log as JLog;
use SimpleXMLElement;
use Carbon\Carbon;
use Mcmraak\Rivercrs\Classes\ParserLog;

class Parser
{

    public function curlGet($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public $cache_on = true;
    public $max_curl_time = false;
    public $cache_exist_test = false;
    public $registerErrors = false;

    public $curl_url;
    public function get($type='json', $url, $get=null, $cache_time=1440)
    {
        if($get) $get = '?'.http_build_query($get);
        $url = $url.$get;
        #Log::debug('Parse url:'.$url); # DEBUG
        #JLog::add('debug','Parser@get', $url, $url);

        if(!$this->update_cache) {
            if($this->cache_on && $cache_time!=0) {
                $cache = $this->getCache($url, $cache_time);
            } else {
                $cache = false;
            }

            if($cache != false) {
                if(isset($cache[0]) && $cache[0] == 'empty') {
                    #JLog::add('error','Parser@get', 'Содержимое массива:'.print_r($cache, 1), $url);
                    return ['error' => 'Запрос не вернул данные (Данные в кэше)'];
                };

                #TODO: if $this->cache_exist_test

                return $cache;
            };
        } else {
            $cache = false;
        }

        $curl = curl_init();

        $this->curl_url = $url;

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        if($this->max_curl_time) {
            curl_setopt ($curl, CURLOPT_TIMEOUT, $this->max_curl_time);
        }

        $response = curl_exec($curl);
        curl_close($curl);


        if($response) {
            if($type=='xml') {
                try {
                    error_reporting(0);
                    $xml = simplexml_load_string($response);
                } catch (\Exception|\Throwable $exception) {
                    $this->error('simplexml_load_string error: '.$exception->getMessage());
                    file_put_contents(
                        storage_path('logs/parser_error_response.txt'),
                        $response
                    );
                }

                if (!$xml) {
                    $this->error('xml parsing error');
                }
                $json = json_encode($xml, JSON_UNESCAPED_UNICODE);
                $array = json_decode($json, 1);
            }

            elseif($type=='xmlv') {
                $array = $this->xmlVals($response);
            }

            elseif($type=='xmlx') {
                $array = $this->xmlToObject($response);
            }

            else {
                $array = json_decode($response,1);
                if(!$array) $array = ['empty'];
            }
        } else {
            $array = ['empty'];
        }

        if($this->cache_on && $cache_time!=0) $this->putCache($url, $array);

        if(isset($array[0]) && $array[0] == 'empty') {
            JLog::add('error','Parser@get', print_r($cache, 1), $url);

            /* #TODO:depricated
            if($this->registerErrors) {
                ParserLog::saveError($this->registerErrors, $url, 'Запрос не вернул данные');
            }
            */

            return ['error' => "Запрос не вернул данные (Прямой запрос: <a target='_blank' href='$url'>$url</a>)"];
        };

        return $array;
    }

    public function diffInIncompliteDays($date_a, $date_b){
        $date_a = substr($date_a, 0,11).'00:00:00';
        $date_b = substr($date_b, 0,11).'00:00:00';
        $start = Carbon::parse($date_a);
        $end = Carbon::parse($date_b);
        $diff = $end->diffInDays($start);
        $diff++;
        return $diff;
    }

    public function xmlToObject($xml) {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        $return = [];

        foreach ($vals as $v) {
            $tag = $v['tag'];
            $line = [];
            foreach ($v as $k => $e) {
                if($k == 'tag') continue;
                $line[$k] = $e;
            }
            $return[$tag][] = $line;
        }
        return $return;
    }

    public function xmlVals($xml) {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        return $vals;
    }

    public function echoJson($array){
        echo json_encode ($array, JSON_UNESCAPED_UNICODE);
    }

    public $live_time;
    # return array or false
    public function getCache($url, $cache_time)
    {

        $file_name = md5($url);
        $file_name = $this->getPath($file_name);

        $cache_filename = base_path().'/storage/rivercrs_cache/'.$file_name.'.gz';
        if(!file_exists($cache_filename)) {
            return false;
        }
        $live = time() - filectime($cache_filename);
        $this->live_time = floor(($cache_time*60 - $live) / 60);

        if($live > $cache_time*60) {
            unlink($cache_filename);
            return false;
        }

        $data = file_get_contents($cache_filename);
        $data = substr($data,10,-8);
        $data = gzinflate($data);
        $data = unserialize($data);

        # Строгий режим
        if(isset($data[0]) && $data[0] == 'empty') {
            if(CacheSettings::get('strict')) unlink($cache_filename);
            return false;
        }

        return $data;
    }

    public function putCache($url, $data)
    {
        # Строгий режим (Если включён, то пустой кеш не создаётся)
        if(isset($data[0]) && $data[0] == 'empty') {
            if(CacheSettings::get('strict')) return;
        }


        $file_name = md5($url);
        $file_name = $this->getPath($file_name);
        $cache_filename = base_path().'/storage/rivercrs_cache/'.$file_name.'.gz';
        $data = serialize($data);
        $data = gzencode($data,9);

        if(!file_exists($cache_filename)) {
            if(!file_exists(dirname($cache_filename)))
                mkdir(dirname($cache_filename), 0777, true);
                file_put_contents($cache_filename, $data);
        }
    }

    public function testTimeCache($url, $cache_time)
    {
        $file_name = md5($url);
        $file_name = $this->getPath($file_name);
        $cache_filename = base_path().'/storage/rivercrs_cache/'.$file_name.'.gz';

        if(!file_exists($cache_filename)) {
            //dd('Проверяю: '.$url);
            return false;
        }
        $live = time() - filectime($cache_filename);

        if($live > $cache_time*60) {
            return false;
        }
        return true;
    }

    public function getPath($filename)
    {
        return implode('/', array_slice(str_split($filename, 3), 0, 3)) . '/' . $filename;
    }

    public function error($message)
    {
        die($this->echoJson(['error'=> $message ]));
    }

    public $update_cache;

    public function cacheWarmUp($method, $type='null', $vars=false, $time_limit=false, $registerErrors=false, $update_cache=false, $return_url=false) {
        $this->update_cache = $update_cache;

        $vars = ($vars)?:(Input::all())?:false;
        $vars = ($vars) ? (object) $vars : false;
        $answer = false;

        if($time_limit) {
            $this->max_curl_time = $time_limit;
        }

        if($registerErrors) {
            $this->registerErrors = $registerErrors;
        }

        /*** Парсеры Водоходъ ***/
        # Документация: https://vodohod.com/agency/api/
        # Описание дампов: http://8ber.ru/s/sws

        # Водоходъ - Теплоходы
        # Запрос: https://api.vodohod.com/json/v2/motorships.php?pauth=kefhjkdRgwFdkVHpRHGs
        # Дамп: http://azimut.dc/rivercrs/api/v2/cache/debug/waterway-motorships
        if($method == 'waterway-motorships') {
            $answer = $this->get('json',
                'https://api.vodohod.com/json/v2/motorships.php',
                ['pauth' => 'kefhjkdRgwFdkVHpRHGs'],
                ($type=='array_now')?0:CacheSettings::get('waterway_motorships')
            );
        }

        # Водоходъ - Круизы
        # Запрос: https://api.vodohod.com/json/v2/cruises.php?pauth=kefhjkdRgwFdkVHpRHGs
        # Дамп: http://azimut.dc/rivercrs/api/v2/cache/debug/waterway-cruises
        if($method == 'waterway-cruises') {
            $answer = $this->get('json',
                'https://api.vodohod.com/json/v2/cruises.php',
                ['pauth' => 'kefhjkdRgwFdkVHpRHGs'],
                ($type=='array_now')?0:CacheSettings::get('waterway_cruises')
            );
        }

        # Водоходъ - Цены круиза с наличием мест
        # Запрос: https://api.vodohod.com/json/v2/cruise-prices.php?pauth=kefhjkdRgwFdkVHpRHGs&cruise=7868
        # Дамп: http://azimut.dc/rivercrs/api/v2/cache/debug/waterway-prices/7868
        if($method == 'waterway-prices') {
            if(!isset($vars->id)) $this->error('Error: variable "cruise" not defined');
            $answer = $this->get('json',
                'https://api.vodohod.com/json/v2/cruise-prices.php',
                [
                    'pauth' => 'kefhjkdRgwFdkVHpRHGs',
                    'cruise' => $vars->id
                ],
                ($type=='array_now')?0:CacheSettings::get('waterway_prices')
            );
        }

        # Водоходъ - Расписание круиза по дням
        # Запрос: https://api.vodohod.com/json/v2/cruise-days.php?pauth=kefhjkdRgwFdkVHpRHGs&cruise=7868
        # Дамп: http://azimut.dc/rivercrs/api/v2/cache/debug/waterway-route/7868
        if($method == 'waterway-route') {
            if(!isset($vars->id)) $this->error('Error: variable "cruise" not defined');
            $answer = $this->get('json',
                'https://api.vodohod.com/json/v2/cruise-days.php',
                [
                    'pauth' => 'kefhjkdRgwFdkVHpRHGs',
                    'cruise' => $vars->id
                ],
                ($type=='array_now')?0:CacheSettings::get('waterway_route')
            );
        }


        /*

        # Водоходъ - Теплоходы [ /rivercrs/api/v2/cache/debug/waterway-motorships ]+
        if($method == 'waterway-motorships') {
            $answer = $this->get('json',
                'http://cruises.vodohod.com/agency/json-motorships.htm',
                ['pauth' => 'kefhjkdRgwFdkVHpRHGs'],
                ($type=='array_now')?0:CacheSettings::get('waterway_motorships')
            );
        }

        # Водоходъ - Круизы [ /rivercrs/api/v2/cache/debug/waterway-cruises ]+
        if($method == 'waterway-cruises') {
            $answer = $this->get('json',
                'http://cruises.vodohod.com/agency/json-cruises.htm',
                ['pauth' => 'kefhjkdRgwFdkVHpRHGs'],
                ($type=='array_now')?0:CacheSettings::get('waterway_cruises')
            );
            //dd($answer['6555']['directions']);
        }

        # Водоходъ - Цены на круизы [ /rivercrs/api/v2/cache/debug/waterway-prices/7854 ]+
        if($method == 'waterway-prices') {
            if(!isset($vars->id)) $this->error('Error: variable "cruise" not defined');
            $answer = $this->get('json',
                'http://cruises.vodohod.com/agency/json-prices.htm',
                [
                    'pauth' => 'kefhjkdRgwFdkVHpRHGs',
                    'cruise' => $vars->id
                ],
                ($type=='array_now')?0:CacheSettings::get('waterway_prices')
            );
        }

        # Водоходъ - Расписание круиза [ /rivercrs/api/v2/cache/debug/waterway-route/6555 ]+
        if($method == 'waterway-route') {
            if(!isset($vars->id)) $this->error('Error: variable "cruise" not defined');
            $answer = $this->get('json',
                'http://cruises.vodohod.com/agency/json-days.htm',
                [
                    'pauth' => 'kefhjkdRgwFdkVHpRHGs',
                    'cruise' => $vars->id
                ],
                ($type=='array_now')?0:CacheSettings::get('waterway_route')
            );
        }


        */


        # http://www.volgawolga.ru/php/xml/
        # http://test.volgawolga.ru/php/xml/

        # ВолгаWolga - База данных [ /rivercrs/api/v2/cache/debug/volgawolga-database-short ]+
        if($method == 'volgawolga-database-short') {
            $answer = $this->get('xml',
                //'http://www.volgawolga.ru/php/xml/',
                self::getDomain().'/rivercrs/debug/Service@getVolgaDB',
                null,
                ($type=='array_now')?0:CacheSettings::get('volgawolga_database')
            );
        }

        # http://www.volgawolga.ru/php/xml/index-tst.php
        # http://test.volgawolga.ru/php/xml/index-tst.php

        # ВолгаWolga - База данных [ /rivercrs/api/v2/cache/debug/volgawolga-database ]+
        if($method == 'volgawolga-database') {
            $answer = $this->get('xml',
                //'http://www.volgawolga.ru/php/xml/index-tst.php',
                self::getDomain().'/rivercrs/debug/Service@getVolgaDB',
                null,
                ($type=='array_now')?0:CacheSettings::get('volgawolga_database')
            );
            //dd($answer['cruises']['cruise']);
        }

        # Гама - Информация о круизах [ /rivercrs/api/v2/cache/debug/gama-cruises ]+
        if($method == 'gama-cruises') {
            $answer = $this->get('xml',
                'https://gama-nn.ru/execute/navigation',
                null,
                ($type=='array_now')?0:CacheSettings::get('gama_cruises')
            );
        }

        # Гама - Справочник городов [ /rivercrs/api/v2/cache/debug/gama-towns ]+
        if($method == 'gama-towns') {
            $answer = $this->get('xml',
                'https://gama-nn.ru/execute/directory/towns',
                null,
                ($type=='array_now')?0:CacheSettings::get('gama_towns')
            );
        }

        # Гама - Справочник категорий кают [ /rivercrs/api/v2/cache/debug/gama-cabins ]+
        if($method == 'gama-cabins') {
            $answer = $this->get('xml',
                'https://gama-nn.ru/execute/directory/category',
                null,
                ($type=='array_now')?0:CacheSettings::get('gama_cabins')
            );
        }

        # Гама - Маршруты всех теплоходов [ /rivercrs/api/v2/cache/debug/gama-paths ]+
        if($method == 'gama-paths') {
            $answer = $this->get('xml',
                'https://gama-nn.ru/execute/direcory/path',
                null,
                ($type=='array_now')?0:CacheSettings::get('gama_paths')
            );
        }

        # Гама - Справочник стран [ /rivercrs/api/v2/cache/debug/gama-countries ]+
        if($method == 'gama-countries') {
            $answer = $this->get('xml',
                'https://gama-nn.ru/execute/directory/nationality',
                null,
                ($type=='array_now')?0:CacheSettings::get('gama_countries')
            );
        }

        # Гама - Информация о круизе [ /rivercrs/api/v2/cache/debug/gama-cruise/14956 ]+
        if($method == 'gama-cruise') {
            if(!isset($vars->id)) $this->error('Error: variable "cruise" not defined');

            if($type == 'array_now') {
                $answer = $this->get('xml',
                    'https://gama-nn.ru/execute/way/'.$vars->id,
                    null,
                    0
                );
            } else {
                $answer = $this->get('xml',
                    'https://gama-nn.ru/execute/way/'.$vars->id .'/all',
                    null,
                    CacheSettings::get('gama_cruise')
                );
            }
        }

        # Гама - Справочник теплоходов [ /rivercrs/api/v2/cache/debug/gama-ships ]+
        # ссылка на графическое изображение, список кают, их категории и маркеры, расположение на схеме
        if($method == 'gama-ships') {
            $answer = $this->get('xml',
                'https://api.gama-nn.ru/execute/view/ships',
                null,
                ($type=='array_now')?0:CacheSettings::get('gama_ships')
            );
        }

        # Гама - Информация о палубе [ /rivercrs/api/v2/cache/debug/gama-deck/15 ]+
        if($method == 'gama-deck') {
            if(!isset($vars->id)) $this->error('Error: variable "deck" not defined');
            $answer = $this->get('xml',
                'https://api.gama-nn.ru/execute/view/deck/'.$vars->id,
                null,
                ($type=='array_now')?0:CacheSettings::get('gama_deck')
                );
        }

        # Гермес - Справочник теплоходов [ /rivercrs/api/v2/cache/debug/germes-ships ]+
        if($method == 'germes-ships') {
            $answer = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/ListTeplohod.php',
                null,
                ($type=='array_now')?0:CacheSettings::get('germes_ships')
                );
        }

        # Гермес - Справочник городов отправления [ /rivercrs/api/v2/cache/debug/germes-sity ]+
        if($method == 'germes-sity') {
            $answer = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/ListCity.php',
                null,
                ($type=='array_now')?0:CacheSettings::get('germes_sity')
                );
        }

        # Гермес - Справочник туров (круизов) [ /rivercrs/api/v2/cache/debug/germes-cruises ]+
        if($method == 'germes-cruises') {
            $answer = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/exportTur.php',
                null,
                ($type=='array_now')?0:CacheSettings::get('germes_cruises')
            );
        }

        # Гермес - Справочник категорий кают [ /rivercrs/api/v2/cache/debug/germes-cabins ]+
        if($method == 'germes-cabins') {
            $answer = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/ListClassKauta.php',
                null,
                ($type=='array_now')?0:CacheSettings::get('germes_cabins')
                );
        }

        # Гермес - Сводная таблица [ /rivercrs/api/v2/cache/debug/germes-cabins-pivot ]
        if($method == 'germes-cabins-pivot') {
            $answer = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/ListKauta.php',
                null,
                ($type=='array_now')?0:CacheSettings::get('germes_cabins')
            );
        }

        # Гермес - Справочник кают тура со статусами и ценами [ /rivercrs/api/v2/cache/debug/germes-status/12278 ]+
        if($method == 'germes-status') {
            if(!isset($vars->id)) $this->error('Error: variable "tur" not defined');

            $answer = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/exportKauta.php',
                ['tur' => $vars->id],
                ($type=='array_now')?0:CacheSettings::get('germes_status')
            );
        }

        # Гермес - Список городов следования в туре [ /rivercrs/api/v2/cache/debug/germes-trace/13299 ]
        if($method == 'germes-trace') {
            if(!isset($vars->id)) $this->error('Error: variable "tur" not defined');
            $answer = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/exportTrace.php',
                ['tur' => $vars->id],
                ($type=='array_now')?0:CacheSettings::get('germes_trace')
            );
        }

        # Гермес - Список экскурсий в туре по городам следования [ /rivercrs/api/v2/cache/debug/germes-excursion/13299 ]
        if($method == 'germes-excursion') {
            if(!isset($vars->id)) $this->error('Error: variable "tur" not defined');
            $answer = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/exportExcursion.php',
                ['tur' => $vars->id],
                ($type=='array_now')?0:CacheSettings::get('germes_excursion')
            );
        }

        #### ИнфоФлот #### v2 Описание: https://restapi.infoflot.com/docs

        $info_flot_key = 'b5262f5d8de5be65b201bb5e3f5e544a245b6082';

        /*
        # Инфофлот - Список теплоходов [ /rivercrs/api/v2/cache/debug/infoflot-ships ]
        if($method == 'infoflot-ships') {
            $answer = $this->get('json',
                "http://api.infoflot.com/JSON/$info_flot_key/Ships/",
                null,
                ($type=='array_now')?0:CacheSettings::get('infoflot_ships')
            );
        }
        */

        # Инфофлот - Список теплоходов [ /rivercrs/api/v2/cache/debug/infoflot2-ships?page=1&limit=1 ]
        # https://restapi.infoflot.com/ships?key=b5262f5d8de5be65b201bb5e3f5e544a245b6082 // 17M
        if($method == 'infoflot-ships') {

            $page = @$vars->page;
            $limit = @$vars->limit;
            if(!$page || !$limit) die('Ошибка: не указаны page или limit');
            $answer = $this->get('json',
                "https://restapi.infoflot.com/ships?key=$info_flot_key&page=$page&limit=$limit",
                null,
                ($type=='array_now')?0:CacheSettings::get('infoflot_ships')
            );
        }

        # Инфофлот - Справочник городов [ /rivercrs/api/v2/cache/debug/infoflot2-towns ]
        if($method == 'infoflot-towns') {
            $answer = $this->get('json',
                "https://restapi.infoflot.com/cities?key=$info_flot_key",
                null,
                ($type=='array_now')?0:CacheSettings::get('infoflot_towns')
            );
        }


        # Инфофлот v2 - Справочник круизов [ /rivercrs/api/v2/cache/debug/infoflot-cruises ]
                    # - Справочник круиза  [ /rivercrs/api/v2/cache/debug/infoflot-cruises/332616 ]
        if($method == 'infoflot-cruises') {
            $id = @$vars->id;
            $page = (@$vars->page)?"&page={$vars->page}":'';
            $limit = (@$vars->limit)?"&limit={$vars->limit}":'';
            $ship = (@$vars->ship)?"&ship={$vars->ship}":'';
            $dateFrom = (@$vars->date)?"&dateStartFrom=$vars->date":'';

            $send_url = "https://restapi.infoflot.com/cruises/$id?key=$info_flot_key".$dateFrom.$page.$limit.$ship;

            $answer = $this->get('json',
                $send_url,
                null,
                ($type=='array_now')?0:CacheSettings::get('infoflot_tours')
            );

            #$this->answerLog($send_url, $answer);

        }

        # Инфофлот v2 - Статусы кают [ /rivercrs/api/v2/cache/debug/infoflot2-cabins/326863 ]
        if($method == 'infoflot-cabins') {
            $cruise_id = @$vars->id;
            $answer = $this->get('json',
                "https://restapi.infoflot.com/cruises/$cruise_id/cabins?key=$info_flot_key",
                null,
                ($type=='array_now')?0:CacheSettings::get('infoflot_tours')
            );
        }



        # Инфофлот v2 - Популярные маршруты [ /rivercrs/api/v2/cache/debug/infoflot2-popular-routes/ ]
        if($method == 'infoflot-popular-routes') {
            $id = @$vars->id;
            $answer = $this->get('json',
                "https://restapi.infoflot.com/popular-routes/$id?key=$info_flot_key",
                null,
                ($type=='array_now')?0:CacheSettings::get('infoflot_routes')
            );
        }


        if($type=='array_now') $type = 'array';

        if($type=='debug') dd($answer);
        if($type=='array' && $return_url) {
            return [
                'answer' => $answer,
                'url' => $this->curl_url
            ];
        }
        if($type=='array') return $answer;
        if($type=='json') {
            if(isset($answer['error'])) $this->error($answer['error']);
            $this->echoJson($answer);
        }

        if($type=='null') {
            if(!isset($answer['error'])) {
                $this->echoJson([
                    'status' => true,
                    'live' => $this->live_time,
                    'error' => false,
                ]);
            } else {
                $this->echoJson([
                    'status' => false,
                    'live' => '',
                    'error' => $answer['error']
                ]);
            }
        }

        if($type == 'check') {
            if(isset($answer['error'])) {
                return $answer['error'];
            } else {
                return false;
            }
        }

    }

    # Получить домен
    public static function getDomain ()
    {
        return env('APP_URL');
//        if (\Request::secure())
//        {
//            return 'https://'.$_SERVER['HTTP_HOST'];
//        } else {
//            return 'http://'.$_SERVER['HTTP_HOST'];
//        }
    }

    function answerLog($url, $answer)
    {
        $path = base_path('storage/logs/answers');
        $path_data = $path.'/data';
        $path_db = $path.'/db';

        if(!file_exists($path)) {
            mkdir($path);
        }
        if(!file_exists($path_data)) {
            mkdir($path_data);
        }


        file_put_contents($path_db, $url."\n", FILE_APPEND);

        $count = (count(file($path_db))) - 1;


        file_put_contents($path_data."/$count.array", serialize($answer));
    }


}
