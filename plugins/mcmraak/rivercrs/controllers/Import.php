<?php namespace Mcmraak\Rivercrs\Controllers;

use BackendMenu;
use Backend\Classes\Controller;

/**
 * Import Back-end Controller
 */
class Import extends Controller
{
    // public $implement = [
    //     'Backend.Behaviors.FormController'
    // ];
    //
    // public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-xls');
    }
    
    public function index() {
       $motorships = \Mcmraak\Rivercrs\Models\Motorships::getMotorshipsWitchCheckins();
       return \View::make('mcmraak.rivercrs::import',['motorships' => $motorships]);
    }
}
