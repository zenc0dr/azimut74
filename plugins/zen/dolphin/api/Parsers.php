<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;

class Parsers extends Core
{
    # http://azimut74/zen/dolphin/api/parsers:go
    function go()
    {
        $this->isAdmin();

        if(!$this->processIsLaunched('dolphin:go')) {
            $this->artisanExec('dolphin:go');
        } else {
            $this->killProcess('dolphin:go');
        }
    }
}
