<?php namespace Mcmraak\Rivercrs\Classes;

use October\Rain\Database\Model;
use View;

class LandingSettings extends Model
{
    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    public $settingsCode = 'mcmraak_rivercrs_landingsettings';

    public $settingsFields = 'fields.yaml';

    public $rules = [
    ];

    static function render()
    {
        $data = [
            'logo' => self::get('logo'),
            'logo_mobile' => self::get('logo_mobile'),
            'poster1' => self::get('poster1'),
            'poster2' => self::get('poster2'),
            'title' => str_replace('<br>', '', self::get('title')),
            'subtitle' => self::get('subtitle'),
            'edge1' => self::get('edge1'),
            'edge2' => self::get('edge2'),
            'edge3' => self::get('edge3'),
            'button' => self::get('button'),
        ];


        return View::make('mcmraak.rivercrs::landing', $data)->render();
    }

    function getStyleAttribute()
    {
        return file_get_contents(base_path('plugins/mcmraak/rivercrs/assets/css/rivercrs.landing.css'));
    }

    function setStyleAttribute($style)
    {
        file_put_contents(base_path('plugins/mcmraak/rivercrs/assets/css/rivercrs.landing.css'), $style);
    }

    function getTemplateAttribute()
    {
        return file_get_contents(base_path('plugins/mcmraak/rivercrs/views/landing.htm'));
    }

    function setTemplateAttribute($template)
    {
        file_put_contents(base_path('plugins/mcmraak/rivercrs/views/landing.htm'), $template);
    }
}