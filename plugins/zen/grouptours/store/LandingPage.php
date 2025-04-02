<?php namespace Zen\GroupTours\Store;

use Zen\GroupTours\Models\Page;
use Zen\Dolphin\Classes\Core as DolphinCore;

class LandingPage extends Store
{
    # http://azimut.dc/group-tours/kakie-tam-tury-iz-saratova?dump
    public function get($data): ?array
    {
        $page = Page::active()
            ->where('slug', $data['slug'])
            ->first();

        if (!$page) {
            return null;
        }

        $page_groups = (new DolphinCore)->store('PageGroups')->getFromScope('ext');
        $tinyboxGallery = $page->tinyboxGallery();


        return [
            'name' => $page->name, # Название посадночной страницы
            'type' => 'page',
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'preset' => $page->preset,
            'tinybox_gallery' => $tinyboxGallery ? $this->json($tinyboxGallery['images'], true) : null, # Фотогалерея
            'tinybox_title' => $tinyboxGallery['title'] ?? null, # Заголовок фотогалереи
            'features' => $page->getFeatures(), # Фишки
            'reviews_json' => $page->getReviews(true), # Отзывы
            'pageGroups' => $page_groups, # Левое меню объеденённое с дельфином,
            'infoblock' => $page->infoblock
        ];
    }
}
