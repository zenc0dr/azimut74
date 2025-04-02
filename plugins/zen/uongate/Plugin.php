<?php namespace Zen\Uongate;

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
                'label'       => 'UonGate',
                'description' => 'Шлюз U-ON',
                'icon'        => 'icon-cloud-upload',
                'permissions' => ['zen.uongate.main'],
                'class' => 'Zen\Uongate\Models\Settings',
                'order' => 600,
            ]
        ];
    }

    public function register()
    {
        $this->registerConsoleCommand('uongate:lead_push', 'Zen\Uongate\Console\Lead');
    }
}
