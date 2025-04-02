<?php namespace Zen\Dolphin\Models;

use Config;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation as ValidationTrait;
use Zen\Dolphin\Models\Page;

class Settings extends Model
{
    use ValidationTrait;

    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    public $settingsCode = 'zen_dolphin_settings';

    public $settingsFields = 'fields.yaml';

    public $rules = [
    ];

    function getDefaultPageOptions()
    {
        return Page::whereHas('page_block', function ($q) {
            $q->where('type_id', 0);
        })->lists('name', 'id');
    }

    function getDefaultPageAtmOptions()
    {
        return Page::whereHas('page_block', function ($q) {
            $q->where('type_id', 1);
        })->lists('name', 'id');
    }
}
