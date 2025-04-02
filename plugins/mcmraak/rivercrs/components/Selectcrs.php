<?php namespace Mcmraak\Rivercrs\Components;

use Cms\Classes\ComponentBase;

class Selectcrs extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'CRS Selector',
            'description' => 'Виджет подбора туров'
        ];
    }

    public function defineProperties()
    {
        return [
            'ion' => [
                'title'             => 'Подключить ion.js',
                'type'              => 'checkbox',
                'default'           => '1',
            ],
            'jquery' => [
                'title'             => 'Подключить jQuery',
                'type'              => 'checkbox',
                'default'           => '1',
            ]
        ];
    }

    public function onRun()
    {
        $this->page['ion'] = $this->property('ion');
        $this->page['jquery'] = $this->property('jquery');
    }
}
