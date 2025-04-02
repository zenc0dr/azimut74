<?php namespace Mcmraak\Rivercrs\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Backend\Facades\Backend;
use Flash;
use DB;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Classes\Ids;


class Parsers extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-parsers');
    }

    public function index() {
        return \View::make('mcmraak.rivercrs::parsers', [
            'logs_backend_url' => Backend::url('mcmraak/rivercrs/logs'),
            'volga_settings_url' => Backend::url('mcmraak/rivercrs/volgasettings'),
        ]);
    }

    public function onCleanIdsMemory()
    {
        $path = base_path('storage/cms/cache/');
        if(file_exists($path.'gama.ids')) unlink($path.'gama.ids');
        if(file_exists($path.'germes.ids')) unlink($path.'germes.ids');
        if(file_exists($path.'volga.ids')) unlink($path.'volga.ids');
        if(file_exists($path.'waterway.ids')) unlink($path.'waterway.ids');
        //if(file_exists($path.'infoflot.ids')) unlink($path.'infoflot.ids');
        (new Ids('infoflot_cache'))->deleteLike('cruise_seed:');
        Flash::success('Память идентификторов очищена');
    }

    public function onCleanSansCache()
    {
        $this->delDir('./storage/rivercrs_cache');
        mkdir('./storage/rivercrs_cache');
        Flash::success('Кэш удалён');
    }

    public function delDir($folder) {
        if (is_dir($folder)) {
            $handle = opendir($folder);
            while ($subfile = readdir($handle)) {
                if ($subfile == '.' or $subfile == '..') continue;
                if (is_file($subfile)) unlink("{$folder}/{$subfile}");
                else $this->delDir("{$folder}/{$subfile}");
            }
            closedir($handle);
            rmdir($folder);
        } else
            unlink($folder);
    }


    public function onSaveBD(){
        $dbname = config('database.connections.mysql.database');
        $dbuser = config('database.connections.mysql.username');
        $dbpass = config('database.connections.mysql.password');

        $tables = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $dbname)
            ->where('TABLE_NAME', 'like', '%mcmraak_rivercrs%')
            ->select('TABLE_NAME')
            ->get()
            ->pluck('TABLE_NAME')
            ->toArray();
        $tables = join(' ', $tables);
        $query = "mysqldump -u$dbuser -p$dbpass $dbname $tables 2>/dev/null > ./plugins/mcmraak/rivercrs/storage/db_backup.sql";
        #\Log::debug($query);
        exec($query);
        Flash::success('База данных сохранена');
    }

    public function onRestoreBD()
    {
        $dbname = config('database.connections.mysql.database');
        $dbuser = config('database.connections.mysql.username');
        $dbpass = config('database.connections.mysql.password');
        $dump = base_path().'/plugins/mcmraak/rivercrs/storage/db_backup.sql';
        //$dump = file_get_contents($dump);
        //DB::unprepared($dump);
        `mysql -u$dbuser -p$dbpass $dbname < $dump`;
        Flash::success('База данных восстановлена');
    }
}