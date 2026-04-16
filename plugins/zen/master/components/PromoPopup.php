<?php namespace Zen\Master\Components;

use Cms\Classes\ComponentBase;

/**
 * Управление через .env:
 * - PROMO_POPUP_ENABLED (true/false, по умолчанию true)
 * - PROMO_POPUP_DELAY_MS (мс до показа, по умолчанию 60000)
 * - PROMO_POPUP_LINK (URL ссылки в тексте)
 * - PROMO_POPUP_IMAGE (путь к картинке, от корня сайта или полный URL)
 */
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
        $enabled = $this->envBool('PROMO_POPUP_ENABLED', true);
        $this->page['promoPopupEnabled'] = $enabled;

        if (!$enabled) {
            return;
        }

        $this->addCss('/plugins/zen/master/assets/css/promo-popup.css');
        $this->addJs('/plugins/zen/master/assets/js/promo-popup.js');

        $this->page['promoPopupImage'] = env(
            'PROMO_POPUP_IMAGE',
            '/plugins/zen/master/assets/images/banner_ship.jpg'
        );
        $this->page['promoPopupLink'] = env(
            'PROMO_POPUP_LINK',
            'https://vk.com/club69811336?w=wall-69811336_6024'
        );
        $this->page['promoPopupDelayMs'] = max(
            0,
            (int) env('PROMO_POPUP_DELAY_MS', 60000)
        );
    }

    /**
     * @param mixed $default
     */
    protected function envBool($key, $default = true)
    {
        $value = env($key);

        if ($value === null || $value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed === null ? $default : $parsed;
    }
}
