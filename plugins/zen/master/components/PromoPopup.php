<?php namespace Zen\Master\Components;

use Cms\Classes\ComponentBase;

class PromoPopup extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Промо-попап',
            'description' => 'Показывает промо-окно через минуту один раз за сессию.'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->addCss('/plugins/zen/master/assets/css/promo-popup.css');
        $this->addJs('/plugins/zen/master/assets/js/promo-popup.js');

        $this->page['promoPopupImage'] = '/plugins/zen/master/assets/images/banner_ship.jpg';
        $this->page['promoPopupLink'] = 'https://vk.ru';
    }
}
