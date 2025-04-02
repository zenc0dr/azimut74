<?php namespace Mcmraak\Blocks\Models;

use Model;
use DB;

/**
 * Model
 */
class Marker extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_blocks_markers';

    public $attachMany = [
        'images' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true]
    ];

    public function cleantext()
    {
        $text = $this->text;
        $text = str_replace("\r", '', $text);
        $text = str_replace("\n", '', $text);
        return $text;
    }

    public function getTextAttribute($value)
    {
        if(!$value)
        {
        return
            '<div class="yandex-baloon">' . "\n" .
            ' <div class="yandex-baloon-title">Заголовок балуна</div>' . "\n" .
            '  <div class="yandex-baloon-text">' . "\n" .
            '   <a href="#link">Ссылка 1</a>' . "\n" .
            '   <a href="#link">Ссылка 2</a>' . "\n" .
            '  </div>' . "\n" .
            '</div>';
        } else return $value;
    }

    public function afterDelete()
    {
        DB::table('mcmraak_blocks_maps_markers')->
        where('marker_id',$this->id)->
        delete();
    }
}