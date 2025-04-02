<?php namespace Mcmraak\Blocks\Components;

use Cms\Classes\ComponentBase;
use Mcmraak\Blocks\Models\Post;

class Block extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Рекламный блок',
            'description' => 'Рекламный блок по ссылке'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $post = false;
        $url = $_SERVER['REQUEST_URI'];
        $fragments = Post::where('slug', 'like', '%*%')->get();
        if(count($fragments)){
            foreach ($fragments as $fragment){
                $slug = $fragment->slug;
                $slug = str_replace('*','', $slug);
                if(strpos($url, $slug)>-1){
                    $post = $fragment;
                    break;
                }
            }
        } else {
            $post = Post::where('slug', $url)->first();
        }

        if(!$post) return;
        $this->page['block'] = $post;
    }
}
