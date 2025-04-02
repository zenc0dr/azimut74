<?php namespace Mcmraak\Rivercrs\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Mcmraak\Rivercrs\Classes\Keeper;

class Backups extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-backups');
    }

    public function onRestore(){
        $id = $this->params[0];
        $message = Keeper::restoreDump($id);
        Flash::{$message['type']}($message['text']);
    }
}
