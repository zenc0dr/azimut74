<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use Flash;
use Mcmraak\Rivercrs\Classes\Keeper;
use Log;

/**
 * Model
 */
class Backup extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $timestamps = false;
    protected $dates = ['date'];

    public $rules = [
    ];

    public function getSizeFormatedAttribute()
    {
        return self::formatSizeUnits($this->size);
    }

    public $table = 'mcmraak_rivercrs_backups';

    public $tables = [];
    public function setTablesAttribute($tables){
        $this->tables = $tables;
    }

    function clean()
    {
        $backups = self::orderBy('id', 'desc')->get();

        $max_backups = 20;
        $i = 0;
        foreach ($backups as $backup) {
            $i++;
            if($i > $max_backups) $backup->delete();
        }

    }

    public function beforeCreate() {
        $time = time();
        $this->attributes['date'] = date('Y-m-d H:i:s', $time);
        $file_time = date('d-m-Y-H-i-s', $time);
        $this->attributes['filename'] = "dump_{$file_time}.gz";
        $size = Keeper::saveDump($this->attributes['filename'], $this->tables);
        $this->attributes['size'] = $size;
    }

    public function afterCreate()
    {
        Flash::success('Резервная копия создана ['.$this->name.'] размер: '.self::formatSizeUnits($this->size));
    }

    function afterSave()
    {
        $this->clean();
    }

    public function afterDelete()
    {
        $file_path = base_path()."/plugins/mcmraak/rivercrs/storage/backups/{$this->filename}";
        if(file_exists($file_path)) {
            unlink(base_path()."/plugins/mcmraak/rivercrs/storage/backups/{$this->filename}");
        }
    }


    public static function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
