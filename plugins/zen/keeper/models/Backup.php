<?php namespace Zen\Keeper\Models;

use Model;
use Zen\Keeper\Classes\Core;

/**
 * Model
 */
class Backup extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_keeper_backups';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsTo = [
        'site' => [
            'Zen\Keeper\Models\Site',
            'key' => 'site_id'
        ],
    ];

    function getSizeFormatedAttribute()
    {
        if(!$this->size) return 'Скачивается';
        return Core::formatSizeUnits($this->size);
    }

    function afterDelete()
    {
        $backup_path = storage_path("keeper_buckups/backup_id_{$this->id}.zip");
        if(!file_exists($backup_path)) return;
        unlink($backup_path);
    }
}
