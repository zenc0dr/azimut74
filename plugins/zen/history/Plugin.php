<?php namespace Zen\History;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

    public function registerPermissions()
    {
        return [
            'zen.history.main' => [
                'tab'   => 'История поисков и избранное',
                'label' => 'микросервис удобства который сохраняет истории поиска пользователя и его избранное'
            ],
        ];
    }
}
