<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;
use Input;

class Proxy extends Core
{
    # http://azimut74/zen/dolphin/api/proxy:get
    function get()
    {
        $url = post('url');
        $post = post('post');
        echo $this->http($url, $post, 20)->body;
    }
}
