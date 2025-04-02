<?php namespace Zen\Cabox\Models;

use Model;
use Zen\Cabox\Classes\Cabox;
use Zen\Cabox\Classes\Core;
use Yaml;
use File;
use Exception;

/**
 * Model
 */
class Storage extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_cabox_storages';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];


    function getHandlersAttribute($value)
    {
        return json_decode($value, true);
    }

    function setHandlersAttribute($value)
    {
        $this->attributes['handlers'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    function getFullPathAttribute()
    {
        return (new Core)->renderPath($this->path);
    }

    static function getOptions($code)
    {
        $config_path = storage_path('cabox_config.yaml');
        $configs = @Yaml::parseFile($config_path);

        $byId = (preg_match('/\d+/', $code))?true:false;

        if($configs) {
            foreach ($configs as $config) {
                if($byId) {
                    if($config['id'] == $code) return $config;
                } else {
                    if($config['code'] == $code) return $config;
                }
            }
        }

        if($byId) {
            $storage = self::find($code);
        } else {
            $storage = self::where('code', $code)->first();
        }

        if(!$storage) die('error');

        $storage->afterSave();
        return $storage->toArray();

    }

    static function init($options)
    {
        $storage = self::where('code', $options['code'])->first();

        if(!$storage) $storage = new self;

        $storage->code = $options['code'];
        $storage->name = $options['name'];
        $storage->path = $options['path'];
        $storage->time = $options['time'];
        $storage->one_folder = $options['one_folder'];
        $storage->compress = $options['compress'];
        $storage->images = $options['images'] ?? 0;
        $storage->save();
    }

    function getDataAttribute()
    {
        return new Cabox($this->toArray());
    }

    function purge()
    {
        try {
            File::deleteDirectory($this->full_path, 1);
            return true;
        } catch (Exception $ex) {
            return $ex;
        }
    }

    function afterSave()
    {
        $config_path = storage_path('cabox_config.yaml');
        $storages = self::get();
        $config = [];
        foreach ($storages as $storage) {
            $value = $storage->toArray();
            unset($value['handlers']);
            $config[$storage->id] = $value;
            if(!file_exists($storage->full_path)) {
                mkdir($storage->full_path, 0777, true);
            }
        }
        file_put_contents($config_path, Yaml::render($config));
    }

    function afterDelete()
    {
        $config_path = storage_path('cabox_config.yaml');
        $config = @Yaml::parseFile($config_path);
        if(!$config) return;
        unset($config[$this->id]);
        file_put_contents($config_path, Yaml::render($config));
        File::deleteDirectory($this->full_path);
    }
}
