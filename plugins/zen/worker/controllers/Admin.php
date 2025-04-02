<?php namespace Zen\Worker\Controllers;

use October\Rain\Exception\ApplicationException;
use Backend\Classes\Controller;
use Zen\Worker\Classes\Core;
use BackendMenu;
use View;
use Flash;
use DB;

class Admin extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Worker', 'worker-main', 'worker-admin');
    }

    public function index() {
        return View::make('zen.worker::admin');
    }

    function onClearState()
    {
        try {
            Core::cleanWorkerSession();
            Flash::success('Состояние очищено');
        } catch (Exception $ex) {
            throw new ApplicationException('Ошибка: '.$ex->getMessage());
        }
    }
}
