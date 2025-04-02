<?php namespace Zen\Om\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Deliveries extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        $this->addCSS('/plugins/zen/om/assets/css/backend.css');
        parent::__construct();
        BackendMenu::setContext('Zen.Om', 'om-main', 'om-orders');
    }
}