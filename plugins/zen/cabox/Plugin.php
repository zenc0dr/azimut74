<?php namespace Zen\Cabox;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

    function register()
    {
        $this->registerConsoleCommand('cabox:purge', 'Zen\Cabox\Console\Purge');
    }
}
