<?php namespace Mcmraak\Rivercrs\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Techs extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = ['mcmraak.rivercrs'];

    public function __construct()
    {
        parent::__construct();
        # Меню слева не пропадает
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-motorships');
    }
}
