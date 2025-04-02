<?php namespace Zen\Fetcher\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Pools extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController'
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Fetcher', 'main', 'pools');
    }
}
