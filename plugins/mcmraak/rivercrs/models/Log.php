<?php namespace Mcmraak\Rivercrs\Models;

use Model;

/**
 * Model
 */
class Log extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_logs';

    public static function add($type, $method, $text, $url='')
    {
        $log = new self;
        $log->type = $type;
        $log->method = $method;
        $log->text = $text;
        $log->url = $url;
        $log->save();
    }

    public static function addLog($data)
    {
        if(!$data) return;
        if(!is_array($data)) return;
        if(!count($data)) return;
        $log = new self;
        foreach ($data as $key => $val) {
            $log->{$key} = $val;
        }
        $log->save();
    }
}
