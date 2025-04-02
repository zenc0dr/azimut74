<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Models\PageGroup;
use Zen\Grinder\Classes\Grinder;
use Zen\GroupTours\Models\Page as GroupToursPage;
use Zen\GroupTours\Models\Settings as GroupToursSettings;
use BackendAuth;

class PageGroups
{
    # Вывод блока "Популярные направления" на стартовой странице EXT
    public function startWidget($scope = 'ext')
    {
        $page_groups = PageGroup::where('scope', $scope)->orderBy('sort_order')->get();
        $output = [];
        $no_image = '/themes/azimut-tur-pro/assets/images/no-image.png';

        foreach ($page_groups as $page_group) {
            if (!$page_group->pages->count()) {
                continue;
            }
            $output[] = [
                'name' => $page_group->name,
                'image' => Grinder::open($page_group->image)->getThumb() ?? $no_image,
                'items' => $page_group->pages->map(function ($page) {
                    return [
                        'name' => $page->label,
                        'url' => '/ex-tours/ext/' . $page->slug
                    ];
                })->toArray()
            ];
        }

        if ($scope === 'ext') {
            $this->groupToursForStartWidget($output);
        }

        return $output;
    }

    public function groupToursForStartWidget(&$output)
    {
        #TODO: Удалить после релиза
//        if (!BackendAuth::check()) {
//            return ;
//        }

        $items = GroupToursPage::active()
            ->get()
            ->map(function ($page) {
                return [
                    'name' => $page->title,
                    'url' => '/group-tours/' . $page->slug,
                ];
            })
            ->toArray();

        if (!$items) {
            return;
        }

        $no_image = '/themes/azimut-tur-pro/assets/images/no-image.png';
        $image = 'storage/app/media' . GroupToursSettings::get('main_picture');

        $image = Grinder::open($image)->getThumb() ?? $no_image;

        $gt_item = [
            'name' => 'Туры для школьных и взрослых групп',
            'image' => $image,
            'items' => $items
        ];

        $output = array_merge($output, [$gt_item]);
    }

    # Вывод левого меню, состоящего из групп посадочных страниц
    public function getFromScope($scope = 'ext')
    {
        $groups = PageGroup::where('scope', $scope)
            ->orderBy('sort_order')
            ->get();
        $output = [];
        foreach ($groups as $group) {
            $pages = $group->pages()->orderBy('group_sort_order')->get();

            if (!$pages->count()) {
                continue;
            }
            $pages = $pages->map(function ($page) {
                return [
                    'name' => $page->label ?? $page->name,
                    'slug' => '/ex-tours/ext/' . $page->slug
                ];
            })->toArray();
            $output[] = [
                'name' => $group->name,
                'pages' => $pages
            ];
        }

        $this->addGroupTours($output);

        return $output;
    }

    public function addGroupTours(array &$page_groups)
    {
        #TODO: Удалить после релиза
//        if (!BackendAuth::check()) {
//            return ;
//        }

        $group_tours = GroupToursPage::active()
            ->get()
            ->map(function ($page) {
                return [
                    'name' => $page->title,
                    'slug' => '/group-tours/' . $page->slug,
                ];
            })
            ->toArray();

        if (!$group_tours) {
            return;
        }

        $page_groups[] = [
            'name' => 'Туры для школьных и взрослых групп',
            'pages' => $group_tours
        ];
    }
}
