<?php namespace Zen\Reviews\Components;

use Cms\Classes\ComponentBase;

class Sender extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Отправка письма',
            'description' => 'Отправка письма ссылкой со страницы'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->addCss(
            mix('css/sender-app.css', 'plugins/zen/reviews/frontend/assets')
        );
        $this->addJs(
            mix('js/sender-app.js', 'plugins/zen/reviews/frontend/assets'),
            ['defer' => true]
        );
    }
}
