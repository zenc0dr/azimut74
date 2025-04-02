<?php namespace Mcmraak\Rivercrs\Settings;

use Config;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation as ValidationTrait;


class NotifySettings extends Model
{
    use ValidationTrait;

    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    public $settingsCode = 'mcmraak_rivercrs_notifysettings';

    public $settingsFields = 'fields.yaml';

    public $rules = [
    ];
}
