<?php namespace Zen\Dolphin;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Zen\Dolphin\Components\Widget'  => 'DolphinWidget',
        ];
    }

    public function registerSettings()
    {
        return [
            'options' => [
                'label'       => 'Dolphin: Настройки',
                'description' => '',
                'icon'        => 'oc-icon-compass',
                'permissions' => ['zen.dolphin.main'],
                'class' => 'Zen\Dolphin\Models\Settings',
                'order' => 100,
            ]
        ];
    }

    function register()
    {
        $this->registerConsoleCommand('dolphin:go', 'Zen\Dolphin\Console\Go');
        $this->registerConsoleCommand('dolphin:search', 'Zen\Dolphin\Console\Search');
        $this->registerConsoleCommand('dolphin:test', 'Zen\Dolphin\Console\Test');
        $this->registerConsoleCommand('dolphin:import', 'Zen\Dolphin\Console\Import');
        $this->registerConsoleCommand('dolphin:gc', 'Zen\Dolphin\Console\Gc');
        $this->registerConsoleCommand('dolphin:parser', 'Zen\Dolphin\Console\Parser');
        $this->registerConsoleCommand('dolphin:addhotels', 'Zen\Dolphin\Console\Addhotels');
        $this->registerConsoleCommand('dolphin:clearold', 'Zen\Dolphin\Console\ClearOld');
        $this->registerConsoleCommand('dolphin:rendertodo', 'Zen\Dolphin\Console\RenderTodo');
    }
}
