<?php namespace Zen\Dolphin\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;

class Orders extends Controller
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
        BackendMenu::setContext('Zen.Dolphin', 'dolphin-main', 'dolphin-orders');
    }

    public function listExtendQuery($query)
    {
        $term = Input::get('listToolbarSearch.term');
        $query->where('data', 'like', "%$term%");
    }
}
