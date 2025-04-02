<?php namespace Mcmraak\Blocks\Components;

use Cms\Classes\ComponentBase;

class Gallery extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Галерея',
            'description' => 'Галерея в любом месте'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
       $this->addJs('assets/js/gallery.js');
    }
}
