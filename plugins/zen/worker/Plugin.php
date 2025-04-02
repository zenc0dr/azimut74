<?php namespace Zen\Worker;

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
        $this->registerConsoleCommand('worker:go', 'Zen\Worker\Console\Go');
    }
}
