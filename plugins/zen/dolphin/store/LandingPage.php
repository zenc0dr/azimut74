<?php namespace Zen\Dolphin\Store;

use Carbon\Carbon;
use Zen\Dolphin\Models\Page; // Посадочная страница
use Zen\Dolphin\Models\Settings;
use Zen\Dolphin\Classes\Core;
use Input;
use BackendAuth;

class LandingPage extends Core
{
    public string $scope;

    public function getData($scope, $slug)
    {
        $this->scope = $scope;

        if ($slug === 'search') {
            $page_id = $scope === 'ext' ? $this->settings('default_page') : $this->settings('default_page_atm');
            $page = Page::find($page_id);
        } else {
            $page = Page::where('slug', $slug)->first();
        }

        if (!$page) {
            return false;
        }

        $tinyboxGallery = $page->tinyboxGallery();


        return [
            'page_type' => 'LandingPage',
            'admin' => BackendAuth::check() ? 1 : 0,
            'page_id' => $page->id,
            'meta_title' => $page->meta_title ?: $page->name,
            'meta_description' => $page->meta_description,
            'preview' => $this->resize($page->preview_image, ['width' => 400]),
            'tinybox_gallery' => $tinyboxGallery ? $this->json($tinyboxGallery['images'], true) : null,
            'tinybox_title' => $tinyboxGallery['title'] ?? null,
            'features' => $page->getFeatures(),
            'reviews_json' => $page->getReviews(true),
            'preset' => $this->preparePreset($page),
            'name' => $page->name,
            'text' => $page->text,
            'seo_text' => $page->seo_text,
            'text_field' => Settings::get('text_field'),
            'pageGroups' => $this->store('PageGroups')->getFromScope('ext')
        ];
    }

    # Функция обработки пресета страницы
    private function preparePreset($page)
    {
        $query = Input::get('query');
        $preset = $query ?? $page->preset;

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
