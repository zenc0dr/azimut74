<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Models\Cruises;
use Mcmraak\Rivercrs\Models\Reference;
use Mcmraak\Rivercrs\Models\Motorships;
use Mcmraak\Rivercrs\Models\Transit;

class RivercrsLeftmenu
{
    public static function build($cruise, bool $is_transit_page = null): array
    {
        $left_menu = [
            'cruises' => [
                'title' => 'Круизы',
                'items' => []
            ],
            'references' => [
                'title' => 'Полезная информация',
                'items' => []
            ],
            'transits' => [
                'title' => null,
                'items' => []
            ],
            'ships' => [
                'title' => 'Теплоходы',
                'items' => [],
                'after_button' => [
                    'title' => 'Смотреть все',
                    'url' => '/russia-river-cruises/ships'
                ]
            ],
        ];

        $cruises = Cruises::where('active', 1)
            ->orderBy('sort_order')
            ->get();

        foreach ($cruises as $menu_item) {
            if (!$menu_item->frame) {
                if (!$menu_item->link) {
                    $left_menu['cruises']['items'][] = [
                        'url' => '/russia-river-cruises/' . $menu_item->slug,
                        'title' => $menu_item->name
                    ];
                }
                else {
                    $left_menu['cruises']['items'][] = [
                        'url' => $menu_item->link,
                        'title' => $menu_item->name
                    ];
                }
            }
            else {
                $left_menu['cruises']['items'][] = [
                    'url' => '/russia-river-cruises/content/' . $menu_item->slug,
                    'title' => $menu_item->name
                ];
            }
        }

        $references = Reference::orderBy('order')->get();

        foreach ($references as $reference) {
            if ($reference->menu) {
                if (!$reference->link) {
                    $left_menu['references']['items'][] = [
                        'url' => '/russia-river-cruises/references/' . $reference->slug,
                        'title' => $reference->name
                    ];
                }
                else {
                    $left_menu['references']['items'][] = [
                        'url' => $reference->link,
                        'title' => $reference->name
                    ];
                }
            }
        }

        $left_menu['transits']['title'] = ($is_transit_page)
            ? $cruise->cruise->menu_title
            : $cruise->menu_title;

        if ($is_transit_page) {
            foreach ($cruise->cruise->transits as $transit) {
                if ($transit->menu) {
                    $left_menu['transits']['items'][] = [
                        'url' => $transit->slug,
                        'title' => $transit->menu_title
                    ];
                }
            }
        }
        else {
            foreach ($cruise->transits as $transit) {
                if ($transit->menu) {
                    $left_menu['transits']['items'][] = [
                        'url' => '/russia-river-cruises/' . $transit->slug,
                        'title' => $transit->menu_title
                    ];
                }
            }
        }

        $ships = Motorships::cleanNames(20);

        foreach ($ships as $ship) {
            $left_menu['ships']['items'][] = [
                'url' => '/russia-river-cruises/motorship/' . $ship['id'],
                'title' => $ship['name']
            ];
        }

        return $left_menu;
    }
}
