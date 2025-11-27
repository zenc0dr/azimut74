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

    public static function clearCruises()
    {
        // Проверка окружения для безопасности
        if (env('APP_ENV') !== 'dev') {
            throw new ApplicationException('Доступно только в dev окружении');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            DB::table('mcmraak_rivercrs_checkins')->truncate();
            DB::table('mcmraak_rivercrs_checkins_memory')->truncate();
            DB::table('mcmraak_rivercrs_decks_pivot')->truncate();
            DB::table('mcmraak_rivercrs_pricing')->truncate();
            DB::table('mcmraak_rivercrs_nprices')->truncate();
            DB::table('mcmraak_rivercrs_waybills')->truncate();
            DB::table('zen_worker_errors')->truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        } catch (\Exception $ex) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            throw new ApplicationException('Ошибка при очистке базы: ' . $ex->getMessage());
        }
    }
}
