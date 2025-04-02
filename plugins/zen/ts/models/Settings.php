<?php namespace Zen\Ts\Models;

use Config;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation as ValidationTrait;
use Illuminate\Filesystem\Filesystem;

class Settings extends Model
{
    use ValidationTrait;

    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    public $settingsCode = 'zen_ts_settings';

    public $settingsFields = 'fields.yaml';

    public $rules = [
    ];

    public function getThemeOptions()
    {
        $themes = array_values(
            array_diff(
                scandir(
                    themes_path()
                ), ['..', '.']
            )
        );

        $output = [];
        foreach ($themes as $theme) {
            $output[$theme] = $theme;
        }
        return $output;
    }
}
