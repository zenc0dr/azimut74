<?php namespace Mcmraak\Rivercrs\Components;

use Cms\Classes\ComponentBase;

class Widget extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Поисковый виджет',
            'description' => 'Виджет подбора туров'
        ];
    }

    public function defineProperties()
    {
        return [
            'outer_result_container' => [
                'title'             => 'Внешний контейнер с результатами',
                'type'              => 'checkbox',
                'default'           => '0',
            ],
        ];
    }

    function onRun()
    {
        $this->page['outer_result_container'] = $this->property('outer_result_container');


        $this->addCss('/plugins/mcmraak/rivercrs/assets/css/mcmraak.rivercrs.widget.css');
        $this->addJs('/plugins/mcmraak/rivercrs/assets/js/mcmraak.rivercrs.widget.js');

        $this->addCss('/plugins/mcmraak/rivercrs/assets/css/quiz.css');

        # Reviews block
        $this->addCss('/themes/azimut-tur/assets/css/swiper-bundle.min.css');
        $this->addJs('/themes/azimut-tur/assets/js/swiper-bundle.min.js');
        $this->addCss('/plugins/mcmraak/rivercrs/assets/css/inject_reviews.css');

    }
}
