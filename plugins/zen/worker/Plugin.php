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
        $this->registerConsoleCommand('worker:gama-parse', 'Zen\Worker\Console\gama\GamaParse');
    }
}
