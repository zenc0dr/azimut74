<?php namespace Zen\Putpics;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

    public function register()
    {
        $this->registerConsoleCommand('putpics.research', 'Zen\Putpics\Console\Research');
        $this->registerConsoleCommand('putpics.actualize', 'Zen\Putpics\Console\Actualize');
        $this->registerConsoleCommand('putpics.has-images', 'Zen\Putpics\Console\HasImages');
    }
}
