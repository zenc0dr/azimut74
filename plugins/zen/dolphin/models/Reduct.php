<?php namespace Zen\Dolphin\Models;

use Model;
use Zen\Dolphin\Models\Activator;
use DB;

/**
 * Model
 */
class Reduct extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

//    public $hasMany = [
//        'activators' => [
//            Activator::class,
//            'key' => 'reduct_id'
//        ],
//    ];

    public $hasMany = [
        'items' => [
            Page::class,
            'key' => 'pageblock_id'
        ],
    ];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_reducts';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    function getActivatorsAttribute()
    {
        if(!$this->id) return;
        $activators = Activator::where('reduct_id', $this->id)->get();

        if(!$activators) return;

        $output = [];

        foreach ($activators as $activator) {
            $output[] = [
                'before_of' => $activator->before_of,
                'before_to' => $activator->before_to,
                'title' => $activator->title,
                'desc' => $activator->desc,
                'accent' => $activator->accent,
                'decrement' => $activator->decrement,
            ];
        }

        return $output;
    }

    private $activatorsDump;
    function setActivatorsAttribute($value)
    {
        if($value) $this->activatorsDump = $value;
    }

    private function saveActivators()
    {
        if(!$this->activatorsDump) return;

        Activator::where('reduct_id', $this->id)->delete();

        $value = collect($this->activatorsDump)->map(function ($item) {
            $item['reduct_id'] = $this->id;
            return $item;
        })->toArray();

        Activator::insert($value);
    }

    function afterSave()
    {
        $this->saveActivators();
    }

    function afterDelete()
    {
        DB::table('zen_dolphin_reducts_pivot')
            ->where('reduct_id', $this->id)
            ->delete();

        DB::table('zen_dolphin_activators')
            ->where('reduct_id', $this->id)
            ->delete();

        DB::table('zen_dolphin_tarrifs')
            ->where('reduct_id', $this->id)
            ->update([
                'reduct_id' => null
            ]);
    }
}
