<?php namespace Zen\Kit;

use System\Classes\PluginBase;

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
                'paginate_url' => [$this, 'paginateUrl'],
                'http' => [$this, 'writeHttp'],
            ]
        ];
    }

    public function paginateUrl($html)
    {
        return preg_replace("/\?page=(\d)/", '/$1', $html->toHtml());
    }

    public function writeHttp($url)
    {
        if(strpos($url,'http')!==0) {
            return 'http://'.$url;
        }
        return $url;
    }

}
