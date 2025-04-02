<?php namespace Zen\Dolphin\Classes;

use Zen\Dolphin\Models\Hotel;
use Zen\Dolphin\Models\PageBlock;
use Zen\Dolphin\Models\Seoitem;
use Zen\Dolphin\Models\Page;
use Zen\Dolphin\Models\Tour;
use Zen\Dolphin\Models\Link;
use Carbon\Carbon;
use DB;
use View;

class Frontend extends Core
{
    private $scope = 'atm'; # Область данных ext || atm
    private $path; # URL
    private $data;  # Массив данных выводимый на фронт,
    private $scipts = []; # Скрипты JS для вывода на фронт,
    private $meta_title;
    private $meta_description;

    # Доступ из шаблона
    public static function make(&$page)
    {
        $frontend = new self;

        # Наполнение данными
        $page['dolphin'] = $frontend->build();

        # Установка метатегов
        if ($frontend->meta_title) {
            $page->meta_title = $frontend->meta_title;
        }
        if ($frontend->meta_description) {
            $page->meta_description = $frontend->meta_description;
        }

        # Наполнение скриптами
        $scripts = $frontend->getJs();
        if ($scripts) {
            $page->addJs($scripts);
        }
    }

    # Конструктор
    public function build()
    {
        # Получем URL
        $this->path = request()->path();

        # Санитизация
        $this->path = preg_replace('/\/h\d+/', '', $this->path);

        # определяем область данных (Если редирект, вернёт ['redirect' => 'url'])
        if (!$this->makeScope()) {
            return $this->data;
        }

        # Пополняем массив $data данными из посадочной страницы и тура (если мы на ней)
        $this->getPageData();

        # Пополняем массив $data данными тура в зависимости от источника
        $this->getTourData();

        # Пополняем массив $data данными для формирования левого меню
        $this->leftMenuBuild();

        # Пополняем массив $data данными для формирования SEO-меню
        $this->seoMenuBuild();

        if ($this->input('debug')) {
            $this->ddd($this->data);
        }

        return $this->data;
    }

    private function getJs()
    {
        return $this->scipts;
    }

    private function addJs($script_src)
    {
        if (!in_array($script_src, $this->scipts)) {
            $this->scipts[] = $script_src;
        }
    }

    private function makeScope()
    {
        $scopes = [
            'ext', # Экскурсионные туры - Поиск
            'atm', # Экскурсионные туры - Подбор цены
            'exp', # Автобусные туры - Поиск
            'atp'  # Автобусные туры - Подбор цены
        ];


        $path = explode('/', $this->path);

        $scope = @$path[1];
        if (!$scope || !in_array($scope, $scopes)) {
            $this->data['redirect'] = '/ex-tours/ext';
            return null;
        }

        unset($path[0]); # Удалить корень (ex-tours)
        unset($path[1]); # Удалить scope

        $page_url = join('/', $path);

        $type_id = null;

        if ($scope == 'ext' || $scope == 'exp') {
            $type_id = 0;
        }

        if ($scope == 'atm' || $scope == 'atp') {
            $type_id = 1;
        }

        $this->data['scope'] = $scope;
        $this->data['root_scope'] = ($type_id) ? 'atm' : 'ext';

        $this->scope = (object)[
            'name' => $scope,
            'url' => $page_url,
            'type_id' => $type_id
        ];

        return true;
    }

    private function getPageData()
    {
        if ($this->scope->name !== 'atm') {
            return null;
        }

        if (!$this->scope->url) {
            # Если область отображения ATM
            $page = Page::find($this->settings('default_page_atm'));
            $this->data['redirect'] = $page->getUrl("ex-tours/atm/");
            return;
        };


        $page = Page::getByUrl($this->scope->url);

        if (!$page) {
            $this->data['404'] = true;
            return;
        }

        $this->meta_title = $page->meta_title;
        $this->meta_description = $page->meta_description;

        $this->data['page'] = [
            'preview' => $this->resize($page->preview_image, ['width' => 400]),
            'preset' => $this->preparePreset($page),
            'name' => $page->name,
            'text' => $page->text,
            'seo_text' => $page->seo_text,
        ];
    }

    # Функция обработки пресета страницы
    private function preparePreset($page)
    {
        $preset = $page->preset;
        if (!$preset) {
            return;
        }
        $now = Carbon::now();
        $preset = str_replace('{N}', $now->format('d.m.Y'), $preset);

        preg_match_all('/\{N\+(\d+)\}/', $preset, $matches);

        if ($matches) {
            $i = 0;
            foreach ($matches[0] as $entry) {
                $days = intval($matches[1][$i]);
                $date = $now->addDay($days)->format('d.m.Y');
                $preset = str_replace($entry, $date, $preset);
                $i++;
            }
        }

        return $preset;
    }

    private function getTourData()
    {
        if ($this->scope->name !== 'exp' && $this->scope->name !== 'atp') {
            return;
        }


        # Добавляем js-скрипты для работы фронта карточки тура
        $this->addJs('../../plugins/zen/dolphin/assets/js/zen.dolphin.tour.js');

        $tour_url = explode('/', $this->scope->url);

        $token = $tour_url[1];


        # Экскурсионный тур Дельфина
        if ($this->scope->name == 'exp' && $tour_url[0] == 'd') {
            $stream = $this->cache('dolphin.search')->get($token);

            if (!$stream) {
                $this->data['404'] = true;
                return;
            }

            $this->getDolphinTourModel($tour_url);
            return;
        }


        # Экскурсионный тур Азимута
        if ($this->scope->name == 'exp' && $tour_url[0] == 'a') {
            $stream = $this->cache('dolphin.service')->get($token . ':query');

            if (!$stream) {
                $this->data['404'] = true;
                return;
            }

            $this->getAzimutToutModel($tour_url);
            return;
        }

        # Автобусный тур Азимута
        if ($this->scope->name == 'atp') {
            $link = Link::get(array_pop($tour_url));
            if (!$link) {
                $this->data['404'] = true;
                return;
            }
            $this->getAtmTourModel($link);
            return;
        }
    }

    private function getAtmTourModel($link_data)
    {
        $hotel_id = $link_data->hotel_id;
        $tour_id = $link_data->tour_id;

        $tour = $this->model('Tour')->find($tour_id);
        $hotel = $this->model('Hotel')->find($hotel_id);

        $hotel_type = @$hotel->type->name;

        if ($hotel_type) {
            $hotel_type = str_replace('Гостиница', 'гостиницу', $hotel_type);
            $hotel_type = str_replace('гостиница', 'гостиницу', $hotel_type);
            $hotel_type = " $hotel_type";
        }

        $data = [
            'atm_tour' => [
                'image' => $hotel->one_pic,
                'date' => $link_data->tour_date,
                'name' => "Автобусный {$tour->name} в$hotel_type \"{$hotel->name}\", {$tour->duration_text}",
            ],
            'waybill' => $tour->waybill_array,
            'info' => $tour->info,
            'tour_program' => $tour->tour_program,
            'info_tech' => $tour->info_tech,
            'anonce' => $tour->anonce,
            'conditions' => $tour->conditions_array,
            'youtube' => $tour->youtube_link,
            'hotel' => $hotel->data,
            'map' => $this->store('Map')->byHotel($hotel),
            'tour_dates' => join(', ', $tour->tour_dates)
        ];

        $tourData['atm_tour'] = View::make('zen.dolphin::infoblocks.atm_tour', $data)->render();
        $tourData['operator_name'] = $tour->operator_name;

        $this->data['tour'] = $tourData;
        $this->data['infoblock'] = new Infoblock($tourData);
    }

    # Получение данных тура из кеша Дельфина
    private function getDolphinTourModel($tour_url)
    {
        $token = $tour_url[1];
        $tour_eid = $tour_url[2];
        $date = $tour_url[3];

        $tour = $this->store('Dolphin')->getTour($tour_eid);

        $tourData = [
            'date' => $date,
            'dolphin_tour' => $tour,
            'tour_name' => $tour['Name'],
            'waybill' => $this->store('DolphinResults')->getWaybill($tour),
            'important_info' => $tour['ImportantInfo'],
            'warning_info' => $tour['WarnInfo'],
            'paid_for' => $tour['PaidFor'],
            'hotels' => $this->store('Hotels')->byDolphinTour($tour)
        ];

        $this->data['tour'] = $tourData;
        $this->data['infoblock'] = new Infoblock($tourData); # Класс инфоблока принимает массив данных
    }

    # Получение данных тура из базы данных
    private function getAzimutToutModel($tour_url)
    {
        $tour_id = $tour_url[2];
        $tour = Tour::find($tour_id);

        $tourData = [
            'operator_name' => $tour->operator_name,
            'ext_azimut_tour_dates' => 'YES',
            'tour_name' => [
                'text' => $tour->name,
                'days' => $tour->duration,
            ],
            'waybill' => $tour->waybill_array,
            'important_info' => $tour->info,
            'info_tech' => $tour->info_tech,
            'anonce' => $tour->anonce,
            'tour_program' => $tour->tour_program,
            'conditions' => $tour->conditions_array,
            'youtube' => $tour->youtube_link,
            'gallery' => $tour->gallery, # Массив картинок тура
            'hotels' => $this->store('Hotels')->byAzimutTour($tour_id)
        ];

        $this->data['tour'] = $tourData;
        $this->data['infoblock'] = new Infoblock($tourData);
    }

    private function leftMenuBuild()
    {
        $type_id = $this->scope->type_id;
        if ($type_id === null) {
            return;
        }

        $page_blocks = PageBlock::where('type_id', $type_id)->get();

        $left_menu = [];
        foreach ($page_blocks as $page_block) {
            $left_menu[] = [
                'name' => $page_block->name,
                'items' => $page_block->items()->where('nest_depth', 0)->whereActive(1)->get(),
                'options' => $page_block->options
            ];
        }

        $this->data['leftmenu'] = $left_menu;
    }

    private function seoMenuBuild()
    {
        $type_id = $this->scope->type_id;
        if ($type_id === null) {
            return;
        }

        $seo_items_ids = DB::table('zen_dolphin_page_blocks as blocks')
            ->join(
                'zen_dolphin_pages as pages',
                'pages.pageblock_id',
                '=',
                'blocks.id'
            )
            ->join(
                'zen_dolphin_seoitems as items',
                'items.page_id',
                '=',
                'pages.id'
            )
            ->where('pages.active', 1)
            ->where('blocks.type_id', $type_id)
            ->select(
                'items.id'
            )
            ->pluck('id')
            ->toArray();

        $seo_items = Seoitem::whereIn('id', $seo_items_ids)
            ->orderBy('sort_order')
            ->get();

        $this->data['seo_menu'] = $seo_items;
    }
}
