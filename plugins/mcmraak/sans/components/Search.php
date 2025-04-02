<?php namespace Mcmraak\Sans\Components;

use Cms\Classes\ComponentBase;

class Search extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'SANS Поиск',
            'description' => 'Виджет поиска для модуля SANS'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        #$this->addJs('assets/js/vue.min.js');
        #// Инициация vue.js перенесена в /themes/azimut-tur/partials/includes/footer.htm
        $this->addCss('assets/css/sans.search.css');
        $this->addJs('assets/js/sans.search.js');
    }
}
