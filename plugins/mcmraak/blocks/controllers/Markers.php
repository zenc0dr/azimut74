<?php namespace Mcmraak\Blocks\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Markers extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'blocks.usage' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Blocks', 'blocks-main', 'blocks-markers');
    }
}