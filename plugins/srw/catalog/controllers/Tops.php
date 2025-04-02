<?php namespace Srw\Catalog\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Tops extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'srw.catalog.tops' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Srw.Catalog', 'catalog-icon', 'catalog-tops');
    }
}