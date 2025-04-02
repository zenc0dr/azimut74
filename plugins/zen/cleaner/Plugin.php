<?php namespace Zen\Cleaner;

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
                'label'       => 'zen.cleaner::lang.plugin.name',
                'description' => 'zen.cleaner::lang.plugin.description',
                'icon'        => 'oc-icon-trash',
                'permissions' => ['zen.cleaner'],
                'class' => 'Zen\Cleaner\Models\Settings',
                'order' => 600,
            ]
        ];
    }

    public function registerPermissions()
    {
        return [
            'zen.cleaner' => [
                'tab'   => 'Cleaner',
                'label' => 'Access'
                ]
            ];
    }

    public function register()
    {
        $this->registerConsoleCommand('zen.clean', 'Zen\Cleaner\Console\Clean');
        $this->registerConsoleCommand('zen.cleandb', 'Zen\Cleaner\Console\CleanDb');
        $this->registerConsoleCommand('zen.report', 'Zen\Cleaner\Console\Report');
        $this->registerConsoleCommand('zen.smart', 'Zen\Cleaner\Console\SmartClean');
    }
}
