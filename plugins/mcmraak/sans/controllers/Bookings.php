<?php namespace Mcmraak\Sans\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Bookings extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'mcmraak.sans'
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Sans', 'sans-main', 'sans-booking');
    }
}
