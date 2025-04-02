<?php namespace Zen\Forms\Components;

use Cms\Classes\ComponentBase;

class Form extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Форма',
            'description' => 'Добавляет на страницу форму'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        if (request('key') !== 'Hd4bHjd') {
            exit('Access denied, use key.');
        }

        $this->addCss(mix('css/forms.css', 'plugins/zen/forms/frontend/assets'));
        $this->addJs(mix('js/forms-app.js', 'plugins/zen/forms/frontend/assets'));
    }
}
