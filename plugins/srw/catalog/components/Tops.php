<?php namespace Srw\Catalog\Components;

use Cache;
use Request;
use Cms\Classes\ComponentBase;
use System\Classes\ApplicationException;
use Srw\Catalog\Models\Tops as Topitems;

class Tops extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'MCM CatalogTops',
            'description' => 'Избранные товары'
        ];
    }

    public function onRun()
    {
    	$this->page['catalogTops'] = Topitems::get();
    }
}
