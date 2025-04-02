<?php namespace Mcmraak\Sans\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Request;
use Mcmraak\Sans\Models\Page;
use Mcmraak\Sans\Models\Root;
use Mcmraak\Sans\Models\Group;
use Mcmraak\Sans\Models\Hotel;
use Mcmraak\Sans\Models\Article;
use Mcmraak\Sans\Controllers\Parser;
use Carbon\Carbon;

/*
Внёс небольшие изменения в код, в метод getPage(), а именно в Отелях isHotel()
добавил вывод на фронт данных с предыдущей страницы sans_page, чтобы работало левое меню в отелях
левое меню берется с предыдущей страницы, также создал новый метод whatIsUrl() чтобы не повторять код
*/
#darkrogua 27/04/18
class Pages extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Sans', 'sans-main', 'sans-pages');
    }

    public static function arrayFromFile($filename)
    {
        return (file_exists($filename))?unserialize(file_get_contents($filename)):false;
    }

    public static function getPage($cmsPage)
    {
        $url  = Request::path();

        preg_match('/^\/(.*)\/:\?\*$/', $cmsPage->url,$match);
        $url  = str_replace($match[1],'', $url);
        $url  = preg_replace('/^\//','', $url);

        $page = false;

        $page = self::whatIsUrl($url);

        if(self::isHotel($url))
        {

            $cmsPage->addJs('/plugins/mcmraak/sans/assets/js/sans.booking.js');
            $url = preg_replace('/^hotel_/','', $url);
            preg_match('/^(\d+)--(.*)$/', $url, $match);

            if($match) {
                $hotel_id = $match[1];
                $query_key = $match[2];
            } else {
                $hotel_id = $url;
                $query_key = false;
            }




            $hotel = Hotel::find($hotel_id);
            if(!$hotel) {
                $cmsPage->setStatusCode(404);
                return $cmsPage->controller->run('404');
            }


            if($query_key) {
                $cache_file = base_path() . '/storage/sans_cache/queries/' . $query_key . '.gz';
                $query_file = base_path() . '/storage/sans_cache/queries/' . $query_key . '.query';

                $query_data = self::arrayFromFile($query_file);

                if (!$query_data) {
                    return null;
                }



                //dd($query_data);

                /*
                  "resort_id" => "1239"
                  "adults" => "2"
                  "kids" => "2"
                  "kidsAges" => "3,4"
                  "dateFrom" => "17.04.2019"
                  "dateTo" => "17.04.2019"
                  "nightsMin" => "7"
                  "nightsMax" => "7"
                  "search_by_hotel_name" => ""
                  "typeSearch" => "hotel"
                 *
                 *
                 * */

                $start_date = Carbon::parse($query_data['dateFrom']); // ErrorException: Trying to access array offset on value of type bool
                $end_date = Carbon::parse($query_data['dateTo']);
                $diff_days = $end_date->diffInDays($start_date);

                if($diff_days) {
                    $wi_date = $start_date->addDays($diff_days/2);
                } else {
                    $wi_date = $query_data['dateFrom'];
                }



                if($query_data) {
                    //$date = Carbon::parse($from); // парсировать дату

                    $cmsPage['query_memory'] = [
                        'wi_resort_id' => $query_data['resort_id'],
                        'wi_parents_count' => $query_data['adults'],
                        'wi_childrens_count' => $query_data['kids'],
                        'wi_childrens_ages' => $query_data['kidsAges'],
                        'wi_date' => $wi_date,
                        'wi_date_delta_days' => ($diff_days)?$diff_days/2:0,
                        'wi_days_from' => $query_data['nightsMin'],
                        'wi_days_to' => $query_data['nightsMax'],
                        'wi_search_by_hotel_name' => $query_data['search_by_hotel_name']
                    ];
                }


                $results = Parser::getCache($cache_file);

                if(!$results) {
                    $cmsPage->setStatusCode(404);
                    return $cmsPage->controller->run('404');
                }

                $hotel_info = [
                    'hotel' => $hotel,
                    'hotel_bag' => $hotel->bag,
                    'hotel_results' => self::hotelFilter($hotel_id, $results),
                    'query_key' => $query_key
                ];
            } else {
                $hotel_info = [
                    'hotel' => $hotel,
                    'hotel_bag' => $hotel->bag,
                    'hotel_results' => false,
                    'query_key' => false
                ];
            }


            $url = \URL::previous();
            $url = explode('/', $url);
            $url = array_pop($url);
            $url = strtok($url, "?");

            $cmsPage['hotel_info'] = $hotel_info;

            $page = self::whatIsUrl($url);
            if($page) {
                $cmsPage['octositeTitle'] = $page->seo_title;
                $cmsPage['octositeDescription'] = $page->seo_description;
                $cmsPage['octositeKeywords'] = $page->seo_keywords;
                $cmsPage['sans_page'] = $page;
            }

            return;
        }

        if(!$page)
        {
            $cmsPage->setStatusCode(404);
            return $cmsPage->controller->run('404');
        }

        $cmsPage['octositeTitle'] = $page->seo_title;
        $cmsPage['octositeDescription'] = $page->seo_description;
        $cmsPage['octositeKeywords'] = $page->seo_keywords;
        $cmsPage['sans_page'] = $page;

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

    public static function whatIsUrl($url) {
        if(self::isRoot($url))
        {
            $url = preg_replace('/^rt_/','', $url);
            $root = Root::where('slug', $url)->first();
            if($root)
            $page = $root->first_page;
        }

        if(self::isArticle($url))
        {
            $url = preg_replace('/^article_/','', $url);
            $page = Article::where('slug', $url)->first();
        }

        if(self::isGroup($url))
        {
            $url = preg_replace('/^gr_/','', $url);
            $group = Group::where('slug', $url)->first();
            if($group)
            $page = $group->first_page;
        }

        if(self::isPage($url))
        {
            $url = preg_replace('/^page_/','', $url);
            $page = Page::where('slug', $url)->first();
        }
        return isset($page) ? $page : false;

    }

    public static function isRoot($slug)
    {
        return preg_match('/^rt_/', $slug);
    }

    public static function isGroup($slug)
    {
        return preg_match('/^gr_/', $slug);
    }

    public static function isPage($slug)
    {
        return preg_match('/^page_/', $slug);
    }

    public static function isHotel($slug)
    {
        return preg_match('/^hotel_/', $slug);
    }

    public static function isArticle($slug)
    {
        return preg_match('/^article_/', $slug);
    }

}
