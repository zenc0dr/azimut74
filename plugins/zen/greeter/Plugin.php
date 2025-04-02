<?php namespace Zen\Greeter;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Zen\Greeter\Components\Integrator'  => 'Greeter',
        ];
    }

    public function registerSettings()
    {
    }
}
