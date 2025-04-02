<?php namespace Mcmraak\Rivercrs\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Discounts extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
        'Backend\Behaviors\ReorderController'
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        $this->addJs('/plugins/mcmraak/rivercrs/assets/js/rivercrs.discounts.js?v' . uniqid());
        $this->addCss('/plugins/mcmraak/rivercrs/assets/css/rivercrs.discounts.css?v' . uniqid());
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-discounts');
    }
}
