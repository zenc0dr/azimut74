<?php namespace Mcmraak\Rivercrs\Classes;

use Config;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation as ValidationTrait;

class QQSettings extends Model
{
    use ValidationTrait;

    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    public $settingsCode = 'mcmraak_rivercrs_qqsettings';

    public $settingsFields = 'fields.yaml';

    public $rules = [
    ];

    function getDataArrAttribute()
    {
        $file_path = base_path('plugins/mcmraak/rivercrs/classes/qqsettings/data_arr.php');
        if(file_exists($file_path)) {
            return file_get_contents($file_path);
        }
        return '';
    }

    function setDataArrAttribute($value)
    {
        $file_path = base_path('plugins/mcmraak/rivercrs/classes/qqsettings/data_arr.php');
        file_put_contents($file_path, $value);
    }

    function getRoomTypeAttribute()
    {
        $file_path = base_path('plugins/mcmraak/rivercrs/views/qq/roomType.htm');
        if(file_exists($file_path)) {
            return file_get_contents($file_path);
        }
        return '';
    }

    function setRoomTypeAttribute($value)
    {
        $file_path = base_path('plugins/mcmraak/rivercrs/views/qq/roomType.htm');
        file_put_contents($file_path, $value);
    }
}
