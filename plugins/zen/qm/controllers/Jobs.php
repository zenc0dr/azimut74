<?php namespace Zen\Qm\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Jobs extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Qm', 'qm-main');
    }

    public function index() {
        return \View::make('zen.qm::panel');
    }
}