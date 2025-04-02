<?php namespace Zen\Qm;

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
        $this->registerConsoleCommand('qm:go', 'Zen\Qm\Console\Go');
    }

}
