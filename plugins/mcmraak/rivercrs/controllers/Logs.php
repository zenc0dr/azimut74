<?php namespace Mcmraak\Rivercrs\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Mcmraak\Rivercrs\Models\Log as JLog;

class Logs extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-parsers');
        if(!JLog::count()) Flash::success('Журнал чист');
    }

    public function onDeleteAll()
    {
        JLog::truncate();
        Flash::success('Журнал очищен');
    }
}
