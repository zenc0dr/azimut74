<?php namespace Srw\Catalog\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Settings extends Controller
{
    public $implement = ['Backend\Behaviors\ListController'];

    public $listConfig = 'config_list.yaml';
    //public $formConfig = 'config_form.yaml';
    //public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Srw.Catalog', 'catalog-icon', 'catalog-settings');
    }

}
