<?php namespace Zen\Deprecator\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class RuleController extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'global' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Deprecator', 'main');
    }
}
