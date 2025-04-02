<?php namespace Mcmraak\Rivercrs\Classes;

use DB;
use Flash;
use Log;
use Mcmraak\Rivercrs\Models\Backup;

class Keeper
{
    public static function getFilename()
    {
        $time = date('d-m-Y-H-i-s');
        return "dump_{$time}.gz";
    }

    # $name (str), $filename (str), $tables (arr)
    public static function saveDump($filename=null, $tables=[])
    {
        $dbname = config('database.connections.mysql.database');
        $dbuser = config('database.connections.mysql.username');
        $dbpass = config('database.connections.mysql.password');

        # Если таблицы не указаны
        if(!$tables) {
        $tables = DB::table('information_schema.TABLES')
            ->where('TABLE_SCHEMA', $dbname)
            ->where('TABLE_NAME', 'like', '%mcmraak_rivercrs%')
            ->select('TABLE_NAME')
            ->get()
            ->pluck('TABLE_NAME')
            ->toArray();
        }

        $filename = ($filename)?:self::getFilename();

        $tables = join(' ', $tables);

        $backupDIR = base_path()."/plugins/mcmraak/rivercrs/storage/backups";
        if(!file_exists($backupDIR)) {
            mkdir($backupDIR);
        }

        $filename = base_path()."/plugins/mcmraak/rivercrs/storage/backups/$filename";
        $query = "mysqldump -u$dbuser -p$dbpass $dbname $tables 2>/dev/null | gzip > $filename";

        exec($query);

        if(!file_exists($filename)) {
            Flash::error('Ошибка при сохранении дампа (Файл не создан)');
            return false;
        }

        $size = filesize($filename);

        if(!$size) {
            Flash::error('Ошибка при сохранении дампа (Нулевой размер)');
            return false;
        }

        return $size;
    }

    public static function restoreDump($id)
    {

        $backup = Backup::find($id);
        $filename = base_path()."/plugins/mcmraak/rivercrs/storage/backups/{$backup->filename}";

        if(!file_exists($filename))
        {
            return [
                'type' => 'error',
                'text' => 'Дамп отсутствует, восстановление невозможно!'
            ];
        }

        $dbname = config('database.connections.mysql.database');
        $dbuser = config('database.connections.mysql.username');
        $dbpass = config('database.connections.mysql.password');

        `gunzip -c $filename | mysql -u$dbuser -p$dbpass $dbname 2>/dev/null`;

        return [
            'type' => 'success',
            'text' => 'Дамп восстановлен'
        ];

    }
}