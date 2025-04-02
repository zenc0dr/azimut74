<?php namespace Zen\Cli;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerSettings()
    {
        return [
            'options' => [
                'label'       => 'Cli',
                'description' => 'Плагин работающий с cli процессами',
                'icon'        => 'oc-icon-play-circle',
                'permissions' => ['zen.cli'],
                'class' => 'Zen\Cli\Models\Settings',
                'order' => 500,
                'category'    => '8BER'
            ]
        ];
    }

    function register()
    {
        $this->registerConsoleCommand('cli:test', 'Zen\Cli\Console\Test');
    }
}
