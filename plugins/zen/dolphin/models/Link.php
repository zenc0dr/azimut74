<?php namespace Zen\Dolphin\Models;

use Model;

/**
 * Model
 */
class Link extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_atmlinks';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    static function set($data)
    {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $key = md5($data);
        $link = self::where('key', $key)->first();
        if($link) return  'q' . $link->id;
        $link = new self;
        $link->key = $key;
        $link->data = $data;
        $link->save();
        return 'q' . $link->id;
    }

    static function get($slug)
    {
        $link_id = preg_replace('/\D/', '', $slug);
        $link = Link::find($link_id);
        if(!$link) return;
        return json_decode($link->data);
    }
}
