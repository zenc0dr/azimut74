<?php namespace Zen\Dolphin\Store;

use View;

class Gallery
{
    function get($images_array, $key = null)
    {
        return View::make('zen.dolphin::ui.gallery', ['images' => $images_array, 'key' => $key])->render();
    }
}
