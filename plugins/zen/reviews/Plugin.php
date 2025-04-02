<?php namespace Zen\Reviews;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Zen\Reviews\Components\Reviews'  => 'Reviews',
            'Zen\Reviews\Components\Sender'  => 'Sender',
        ];
    }

    public function registerSettings()
    {
    }

    public function register()
    {
        $this->registerConsoleCommand('reviews:report', 'Zen\Reviews\Console\Report');
    }
}
