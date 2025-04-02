<?php namespace Srw\Catalog;

use Event;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        #mcm:
        return [
            'Srw\Catalog\Components\Catalogtree'  => 'CatalogTree',
            'Srw\Catalog\Components\Catalog'      => 'Catalog',
            'Srw\Catalog\Components\Tops'         => 'CatalogTops',
        ];
    }

    public function registerSettings()
    {
    }
}
