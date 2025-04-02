<?php namespace Zen\GroupTours\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Tours extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController'    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'zen.group-tours.main'
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.GroupTours', 'main', 'tours');
    }
}
