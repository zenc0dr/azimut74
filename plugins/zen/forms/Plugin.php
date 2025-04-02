<?php namespace Zen\Forms;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Zen\Forms\Components\Form'  => 'Forms',
        ];
    }

    public function registerSettings()
    {

    }
}
