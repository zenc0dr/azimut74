<?php namespace Zen\GroupTours;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
        return [
            'options' => [
                'label'       => 'Групповые туры',
                'description' => 'Настройки модуля',
                'icon'        => 'icon-users',
                'permissions' => [],
                'class' => 'Zen\GroupTours\Models\Settings',
                'order' => 600,
            ]
        ];
    }
}
