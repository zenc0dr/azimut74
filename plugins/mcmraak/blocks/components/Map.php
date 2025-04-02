<?php namespace Mcmraak\Blocks\Components;

use Cms\Classes\ComponentBase;
use Mcmraak\Blocks\Models\Map as MapModel;

class Map extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Яндекс карта',
            'description' => 'Яндекс карта по ссылке',
        ];
    }

    public function defineProperties()
    {
        return [];
    }



    public function onRun()
    {
        $map = false;
        $url = $_SERVER['REQUEST_URI'];
        $fragments = MapModel::where('slug', 'like', '%*%')->get();
        if(count($fragments)){
            foreach ($fragments as $fragment){
                $slug = $fragment->slug;
                $slug = str_replace('*','', $slug);
                if(strpos($url, $slug)>-1){
                    $map = $fragment;
                    break;
                }
            }
        } else {
            $map = MapModel::where('slug', $url)->first();
        }

        if(!$map) return;
        $this->page['map'] = $map;
    }
}
