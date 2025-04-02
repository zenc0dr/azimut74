<?php namespace Zen\Dolphin\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use View;
use Flash;
use Input;
use Illuminate\Support\Facades\Artisan;

class Import extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Dolphin', 'dolphin-main', 'dolphin-import');
    }

    public function index() {
        return View::make('zen.dolphin::backend.import');
    }

    public function onUploadCsv()
    {
        $files = Input::file();
        if(!isset($files['import_file'])) {
            Flash::error('Файл не найден');
            return;
        }

        $files['import_file']->move(storage_path('app'), 'dolphin_import.csv');

        $storage_path = storage_path('app/dolphin_import.csv');

        # Конвертация кодировки
        $csv = file_get_contents($storage_path);
        $csv = iconv('windows-1251', 'UTF-8', $csv);
        file_put_contents($storage_path, $csv);


        Artisan::call('dolphin:import');
        Flash::success('Файлы загружены');
    }
}
