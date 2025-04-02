<?php namespace Mcmraak\Blocks\Components;

use Cms\Classes\ComponentBase;
use Mcmraak\Blocks\Models\Scope;

class Injector extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Инъектор баннеров',
            'description' => 'Подключение библиотек'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->page['scopes'] = Scope::get();
        $this->addJs('/plugins/mcmraak/blocks/assets/js/injector.js');
        $this->addCss('/plugins/mcmraak/blocks/assets/css/injector.css');
    }
}
