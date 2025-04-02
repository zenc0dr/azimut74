<?php namespace Zen\Fetcher;

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
                'label'       => 'Fetcher',
                'description' => 'Настройки конвейера парсеров',
                'icon'        => 'oc-icon-angle-double-left',
                'permissions' => [],
                'class' => 'Zen\Fetcher\Models\Settings',
                'order' => 100,
            ]
        ];
    }

    function register()
    {
        $this->registerConsoleCommand('fetcher', 'Zen\Fetcher\Console\Fetcher');
    }
}
