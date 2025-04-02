<?php namespace Mcmraak\Blocks\Components;

use Cms\Classes\ComponentBase;
use Mcmraak\Blocks\Models\Slider;

class Carousel extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Слайдер',
            'description' => 'Слайдер на страницу'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

	public function onRun()
    {
        $url = $_SERVER['REQUEST_URI'];

        $slides = Slider::where('active', 1)->orderBy('order')->get();

        $onlyOne = false;

        $items = [];
        foreach ($slides as $slide)
        {
            $slug = $slide->slug;

            # Входение по секции
            if($slide->to_section) {
                if(strpos($url, $slug)!==false) {

                    if($slide->only) {
                        $items = [];
                        $this->addSlides($items, $slide);
                        break;
                    }

                    $this->addSlides($items, $slide);
                }

            # Вхождение с начала строки
            } else {
                if(strpos($url, $slug) === 0) {

                    if($slide->only) {
                        $items = [];
                        $this->addSlides($items, $slide);
                        break;
                    }

                    $this->addSlides($items, $slide);
                }
            }
        }

        if(!$items) return;
        $this->page['slider_items'] = $items;
    }

    function addSlides(&$items, $slide)
    {
        foreach ($slide->slides as $i) {
            $items[] = $i;
        }
    }

}
