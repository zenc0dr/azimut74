<?php namespace Mcmraak\Rivercrs;

use System\Classes\PluginBase;
use Mcmraak\Rivercrs\Services\RiverSitemap;
use Event;

class Plugin extends PluginBase
{
    public function boot()
    {
        /*
         * Register menu items for the RainLab.Pages and RainLab.Sitemap plugin
         */
        Event::listen('pages.menuitem.listTypes', function () {
            return [
                'river-crs' => 'Ссылки на Rivercrs',
            ];
        });
        Event::listen('pages.menuitem.resolveItem', function ($type) {
            if ($type === 'url') {
                return [];
            }
            if ($type === 'river-crs' ) {
                return ['items' => RiverSitemap::generate()];
            }
        });
    }

    public function registerComponents()
    {
         return [
             'Mcmraak\Rivercrs\Components\Selectcrs'  => 'Selector',
             'Mcmraak\Rivercrs\Components\Widget'  => 'Widget'
        ];
    }

    public function registerSettings()
    {
        return [
            'options' => [
                'label'       => 'RiverCRS: Настройки',
                'description' => 'Настройка модуля',
                'icon'        => 'oc-icon-anchor',
                'permissions' => ['mcmraak.rivercrs'],
                'class' => 'Mcmraak\Rivercrs\Classes\CacheSettings',
                'order' => 600,
            ],
            'qqoptions' => [
                'label'       => 'RiverCRS: Qui-Quo',
                'description' => 'Настройки интеграции Qui-Quo',
                'icon'        => 'oc-icon-anchor',
                'permissions' => ['mcmraak.rivercrs'],
                'class' => 'Mcmraak\Rivercrs\Classes\QQSettings',
                'order' => 600,
            ],
            'landingsettings' => [
                'label'       => 'RiverCRS: Лендинг',
                'description' => 'Настройки лендинга',
                'icon'        => 'oc-icon-anchor',
                'permissions' => ['mcmraak.rivercrs'],
                'class' => 'Mcmraak\Rivercrs\Classes\LandingSettings',
                'order' => 600,
            ],
            'injsettings' => [
                'label'       => 'RiverCRS: Блоки инъектора',
                'description' => 'Настройки блоков инъектора',
                'icon'        => 'oc-icon-anchor',
                'permissions' => ['mcmraak.rivercrs'],
                'class' => 'Mcmraak\Rivercrs\Classes\InjSettings',
                'order' => 600,
            ],
            'notifysettings' => [
                'label'       => 'RiverCRS: Настройки уведомлений',
                'description' => 'Настройки уведомлений',
                'icon'        => 'oc-icon-anchor',
                'permissions' => ['mcmraak.rivercrs'],
                'class' => 'Mcmraak\Rivercrs\Settings\NotifySettings',
                'order' => 600,
            ],
        ];
    }

    public function register()
    {
        $this->registerConsoleCommand('rivercrs.fixmaster', 'Mcmraak\Rivercrs\Console\Fixmaster');
        $this->registerConsoleCommand('rivercrs.checkprices', 'Mcmraak\Rivercrs\Console\CheckPrices');
        $this->registerConsoleCommand('rivercrs.go', 'Mcmraak\Rivercrs\Console\Go');
        $this->registerConsoleCommand('rivercrs:volga', 'Mcmraak\Rivercrs\Console\Volga');
        $this->registerConsoleCommand('rivercrs:service', 'Mcmraak\Rivercrs\Console\Check');
        $this->registerConsoleCommand('rivercrs:recache', 'Mcmraak\Rivercrs\Console\RecacheCheckins');
        $this->registerConsoleCommand('rivercrs:check_data', 'Mcmraak\Rivercrs\Console\CheckData');
    }


    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'price_format' => [$this, 'priceFormat']
            ]
        ];
    }

    public function priceFormat($integer)
    {
        $integer = intval($integer);
        return number_format($integer, 0, ' ', ' ');
    }
}
