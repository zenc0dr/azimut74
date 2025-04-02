<?php namespace Zen\History\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Links extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController'    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.History', 'main-menu-item', 'side-menu-item');
        $this->addCss('/plugins/zen/history/assets/css/backend.css');
    }
}
