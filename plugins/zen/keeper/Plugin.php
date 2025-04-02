<?php namespace Zen\Keeper;

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
                'label'       => 'Keeper',
                'description' => 'Backup sites system',
                'icon'        => 'icon-lock',
                'permissions' => ['zen.keeper'],
                'class' => 'Zen\Keeper\Models\Settings',
                'order' => 100,
            ]
        ];
    }

    function register()
    {
        $this->registerConsoleCommand('keeper:backup', 'Zen\Keeper\Console\Backup');
        $this->registerConsoleCommand('keeper:download', 'Zen\Keeper\Console\Download');
        $this->registerConsoleCommand('keeper:upgrade', 'Zen\Keeper\Console\Upgrade');
        $this->registerConsoleCommand('keeper:go', 'Zen\Keeper\Console\Go');
    }
}
