<?php namespace Zen\Worker\Models;

use Model;
use View;
use Log;
use Zen\Worker\Classes\State;
use Zen\Worker\Classes\ProcessLog;
use Yaml;

/**
 * Model
 */
class Stream extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    public $timestamps = false;
    public $table = 'zen_worker_streams';
    public $rules = [];

    function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    function getDataRepeaterAttribute()
    {
        if(!$this->data) return;
        $repeater = [];
        foreach ($this->data as $key => $opt) {
            $repeater[] = [
                'key' => $key,
                'value' => $opt
            ];
        }
        return $repeater;
    }

    function setDataRepeaterAttribute($opts)
    {
        $arr = [];
        foreach ($opts as $opt) {
            $arr[$opt['key']] = $opt['value'];
        }
        $this->attributes['data'] = json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    function getPoolsAttribute($value)
    {
        if(!$value) return;

        $value = json_decode($value, true);

        $output = [];
        foreach ($value as $pool) {
            $output[] = [
                'active' => filter_var($pool['active'], FILTER_VALIDATE_BOOLEAN),
                'title' => $pool['title'],
                'call' => $pool['call'],
                'self' => filter_var($pool['self'], FILTER_VALIDATE_BOOLEAN),
                'critical' => filter_var($pool['critical'], FILTER_VALIDATE_BOOLEAN),
                'attempts' => intval($pool['attempts']),
                'pause' => intval($pool['pause']),
                'timeout' => intval($pool['timeout']),
            ];
        }

        return $output;
    }

    function setPoolsAttribute($value)
    {
        $this->attributes['pools'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    function getState()
    {
        return @Yaml::parseFile(storage_path("worker/{$this->code}State.yaml"));
    }

    function clearState()
    {
        @unlink(storage_path("worker/{$this->code}State.yaml"));
    }

    # Создать класс-шаблон для потока
    function createPoolBehavior()
    {
        $pool_classes = [];
        foreach ($this->pools as $pool) {
            preg_match('/([a-zA-Z]+)@[a-zA-Z]+$/', $pool['call'], $m);
            if(!@$m[1]) continue;
            $pool_classes[$m[1]] = null;
        }

        $pool_classes = array_keys($pool_classes);

        foreach ($pool_classes as $pool_class) {
            $path = base_path('plugins/zen/worker/pools/'.$pool_class.'.php');
            if(file_exists($path)) continue;
            $dir_path = dirname($path);
            if(!file_exists($dir_path)) mkdir($dir_path, 0777, true);
            $code = View::make('zen.worker::blueprints.pool', ['class' => $pool_class])->render();
            file_put_contents($path, $code);
        }

    }

    # Events
    function afterSave(){
        $this->createPoolBehavior();
        $this->clearState();
    }
}
