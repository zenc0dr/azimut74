<?php namespace Zen\Reviews\Components;

use Cms\Classes\ComponentBase;

class Reviews extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Отзывы',
            'description' => 'Добавляет на страницу форму отзывов'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        if ($email = request('email')) {
            $this->page['email'] = $email;
        }
        $this->addCss(mix('css/reviews.css', 'plugins/zen/reviews/frontend/assets'));
        $this->addJs(mix('js/reviews-app.js', 'plugins/zen/reviews/frontend/assets'));
    }
}
