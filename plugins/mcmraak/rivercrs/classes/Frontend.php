<?php namespace Mcmraak\Rivercrs\Classes;

use Input;
use Mcmraak\Rivercrs\Models\Cruises;
use Mcmraak\Rivercrs\Models\Transit;
use Mcmraak\Rivercrs\Models\Reference;
use Mcmraak\Rivercrs\Models\Motorships;
use Zen\Grinder\Classes\Grinder;
use Carbon\Carbon;

class Frontend
{
    private $path, $slugs, $redirect, $page_data;

    function __construct(&$page)
    {

        $this->path = request()->path();
        $this->slugs = explode('/', $this->path);

        # Получить данные страницы
        $this->getPageData();

        $page['cruise'] = $this->buildCruise();
        $page['left_menu'] = $this->buildLeftMenu();
        $page['filterpreset'] = $this->getFilterPreset();
    }

    function make()
    {
        if($this->redirect == '404') return '$this->setStatusCode(404);return $this->controller->run("404");';
        if($this->redirect) return "return Redirect::to('{$this->redirect}');";
    }

    function getFilterPreset()
    {
        $filter = Input::get('filter');
        if($filter) {
            $filter = explode('-', $filter );
            return [
                'town' =>  intval(@$filter[0]),
                'dest' => intval(@$filter[1]),
                'date_1' => @$filter[2] ?? "",
                'date_2' => @$filter[3] ?? "",
                'days' => intval(@$filter[4]),
                'ship' => intval(@$filter[5]),
            ];
        } else {

            return [
                'town' =>  $this->page_data->town1,
                'dest' => $this->page_data->town2,
                'date_1' => @Carbon::parse($this->page_data->date1)->format('d.m.Y'),
                'date_2' => @Carbon::parse($this->page_data->date2)->format('d.m.Y'),
                'days' => $this->page_data->days,
                'ship' => $this->page_data->ship_id,
            ];
        }

    }


    function getPageData()
    {
        $slug = @$this->slugs[1] ?? 'river-cruises';

        $page_data = Cruises::whereSlug($slug)->first();
        if(!$page_data) {
            $page_data = Transit::whereSlug($slug)->first();
        }

        if(!$page_data) {
            $this->redirect = '404';
        }

        $this->page_data = $page_data;
    }

    function buildLeftMenu()
    {
        return [
            $this->getCruises(),
            $this->getInfo(),
            $this->getTransitions(),
            $this->getShips()
        ];
    }

    function getCruises() {

        $cruises = Cruises::where('active', 1)->orderBy('sort_order')->get();

        $items = [];

        foreach ($cruises as $item) {

            $href = null;

            if(!$item->frame) {
                if(!$item->link) {
                    $href = "/{$this->slugs[0]}/{$item->slug}";
                } else {
                    $href = "/{$this->slugs[0]}/{$item->link}";
                }
            } else {
                $href = "/{$this->slugs[0]}/content/{$item->slug}";
            }

            $items[] = [
                'name' => $item->name,
                'href' => $href,
            ];
        }

        return [
            'title' => 'Круизы',
            'items' => $items
        ];
    }

    function getInfo()
    {
        $references = Reference::orderBy('order')->get();

        $items = [];

        foreach ($references as $item) {
            $items[] = [
                'name' => $item->name,
                'href' => ($item->link) ?: "/{$this->slugs[0]}/references/{$item->slug}",
            ];
        }

        return [
            'title' => 'Полезная информация',
            'items' => $items
        ];
    }

    function getTransitions()
    {
        $transit = class_basename($this->page_data) == 'Transit';
        $transit_items = ($transit) ? $this->page_data->cruise->transits : $this->page_data->transits;

        $items = [];
        foreach ($transit_items as $item) {
            $items[] = [
                'name' => $item->menu_title,
                'href' => "/{$this->slugs[0]}/{$item->slug}",
            ];
        }

        return [
            'title' => ($transit) ? $this->page_data->cruise->menu_title : $this->page_data->menu_title,
            'items' => $items
        ];
    }

    function getShips()
    {
        $ships = Motorships::cleanNames();

        $items = [];
        foreach ($ships as $ship) {
            $items[] = [
                'name' => $ship['name'],
                'href' => "/{$this->slugs[0]}/motorship/{$ship['id']}"
            ];
        }

        return [
            'title' => 'Теплоходы',
            'items' => $items
        ];
    }

    function buildCruise()
    {

        $image = @$this->page_data->images[0];

        return [
            'image' => ($image) ? Grinder::open($image->path)->width(400)->getThumb() : null,
            'text' => $this->page_data->text,
            'seo_articles' => $this->page_data->seo_articles
        ];
    }
}
