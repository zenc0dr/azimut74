<?php namespace Zen\Grinder;

use System\Classes\PluginBase;
use Zen\Grinder\Classes\Filter;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'grinder' => function($image_path, $options) {
                    return (new Filter)->thumbs($image_path, $options);
                },
            ]
        ];
    }
}
