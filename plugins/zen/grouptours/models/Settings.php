<?php namespace Zen\GroupTours\Models;

use Config;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation as ValidationTrait;

/**
 * @method static get($key)
 */
class Settings extends Model
{
    use ValidationTrait;
    public $implement = ['System.Behaviors.SettingsModel',];
    public $settingsCode = 'zen_grouptours_settings';
    public $settingsFields = 'fields.yaml';
    public $rules = [];

    public $attachMany = [
        'files' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ],
    ];
}
