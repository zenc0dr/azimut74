<?php namespace Zen\Dolphin\Components;

use Cms\Classes\ComponentBase;
use Response;
use Zen\Dolphin\Classes\SearchStream;

class Widget extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Dolphin widget',
            'description' => 'Добавить виджет api Дельфин'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun() {
        //$this->page['vue'] = file_get_contents(base_path('plugins/zen/dolphin/vue/dolphin-widget/assets/index.html'));
        $this->page['css'] = mix('css/widget.css', 'plugins/zen/dolphin/vue/dolphin-widget/assets');
        $this->page['js'] = mix('js/main.js', 'plugins/zen/dolphin/vue/dolphin-widget/assets');
    }
}
