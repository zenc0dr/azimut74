<?php namespace Zen\Dolphin\Store;

use Carbon\Carbon;
use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Page;
use Zen\Dolphin\Models\Settings;

class ExtStartPage extends Core
{
    public function getData()
    {
        $tinybox_gallery = collect(Settings::get('start_gallery'))->map(function ($item) {
            return [
                'src' => '/storage/app/media' . $item['image'],
                'caption' => $item['title'] ?? null
            ];
        })->toJson(256);

        $page_id = $this->settings('default_page');
        $page = Page::find($page_id);

        return [
            'page_type' => 'ExtStartPage',
            'meta_title' => 'Экскурсионные туры',
            'preset' => $this->preparePreset($page),
            'start_widget_background' => '/storage/app/media' . $this->settings('ext_background'),
            'start_widget_title' => $this->settings('ext_title'),
            'start_widget_sub_title' => $this->settings('ext_sub_title'),
            'advantages' => collect($this->settings('ext_adv'))->map(function ($item) {
                return $item['html'];
            })->toArray(),
            'popular_directions_title' => 'Направления, темы туров',
            'popular_directions' => $this->store('PageGroups')->startWidget('ext'),
            'reviews_json' => $this->store('Reviews')->get(),
            'tinybox_gallery' => $tinybox_gallery
        ];
    }


    # Функция обработки пресета страницы #TODO:(zen) - Дублирование кода из plugins/zen/dolphin/store/LandingPage.php
    private function preparePreset($page)
    {
        $preset = $page->preset;
        if (!$preset) {
            return null;
        }

        $this->oldTypeRefactor($preset);

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

    private function oldTypeRefactor(&$preset)
    {
        if (!str_starts_with($preset, '#')) {
            return;
        }

        $preset = substr($preset, 1);

        $arr = explode(';', $preset);

        $arr2 = [];
        foreach ($arr as $item) {
            if (!$item) {
                continue;
            }
            $item = explode('=', $item);
            $arr2[$item[0]] = $item[1];
        }

        $dates = explode('-', $arr2['d']);

        $days = $arr2['ds'] ?? [];

        if ($days) {
            $days = explode(',', $days);
            $days = collect($days)->filter(function ($item) {
                return $item !== '0';
            })->map(function ($item) {
                return intval($item);
            })->values()->toArray();
        }

        $preset = json_encode([
            'geo_objects' => $arr2['g'] ? explode(',', $arr2['g']) : [],
            'date_of' => $dates[0],
            'date_to' => $dates[1],
            'days' => $days,
            'adults' => intval($arr2['p']),
            'childrens' => $arr2['a']  ?? null ? explode(',', $arr2['a']) : [],
            'list_type' => 'schedule',
            'labels' => $arr2['labels'] ?? null ? explode(',', $arr2['labels']) : [],
        ], 256);
    }
}
