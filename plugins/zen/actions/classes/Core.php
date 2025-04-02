<?php namespace Zen\Actions\Classes;

use BackendAuth;
use Zen\Actions\Models\Action;
use Input;
use View;

class Core {
    static function run($id)
    {
        if(!BackendAuth::check()) die('Access denied');
        eval(Action::find($id)->code);
    }
}
