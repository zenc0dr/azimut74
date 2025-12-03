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

            // Очистка данных, связанных с заездами
            DB::table('mcmraak_rivercrs_checkins')->truncate();
            DB::table('mcmraak_rivercrs_checkins_memory')->truncate();
            DB::table('mcmraak_rivercrs_pricing')->truncate();
            DB::table('mcmraak_rivercrs_nprices')->truncate();
            DB::table('mcmraak_rivercrs_waybills')->truncate();
            
            // Очистка категорий кают (создаются при импорте)
            DB::table('mcmraak_rivercrs_cabins')->truncate();
            
            // Очистка связей палуб с каютами (создаются при импорте)
            DB::table('mcmraak_rivercrs_decks_pivot')->truncate();
            
            // Очистка палуб, созданных автоматически при импорте
            // Внимание: палубы могут быть созданы вручную, но для чистоты проверки очищаем все
            // При импорте палубы будут пересозданы автоматически через getDeck()
            DB::table('mcmraak_rivercrs_decks')->truncate();
            
            // Очистка логов и ошибок
            DB::table('zen_worker_errors')->truncate();
            
            // Очистка городов, созданных при импорте (id >= 4304 - автоматически созданные)
            DB::table('mcmraak_rivercrs_towns')->where('id', '>=', 4304)->delete();

            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        } catch (\Exception $ex) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            throw new ApplicationException('Ошибка при очистке базы: ' . $ex->getMessage());
        }
    }
}
