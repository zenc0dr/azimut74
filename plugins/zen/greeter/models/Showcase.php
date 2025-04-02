<?php namespace Zen\Greeter\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use Zen\Greeter\Controllers\Showcases;

/**
 * @method static where(string $string, int $int)
 */
class Showcase extends Model
{
    use Validation;

    public $timestamps = false;
    public $table = 'zen_greeter_showcases';
    public $rules = [];

    public $attachOne = [
        'banner_image' => [
            'System\Models\File',
            'delete' => true
        ],
    ];

    public function setAdvantagesAttribute($value)
    {
        $this->attributes['advantages'] = json_encode($value, 256);
    }

    public function getAdvantagesAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getOpacityOptions()
    {
        $output = [];
        for ($i = 1; $i<101; $i++) {
            $output[$i] = $i;
        }

        return $output;
    }

    public function getOpacityValueAttribute()
    {
        return (string) intval($this->opacity) * 0.01;
    }

    public function afterSave()
    {
        Showcases::createCache();
    }

    /* Пока что не сделано, но готово к расширению */
    public function getTemplateCodeOptions()
    {
        return [];
    }

}
