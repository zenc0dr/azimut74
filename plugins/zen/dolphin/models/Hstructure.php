<?php namespace Zen\Dolphin\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use DB;

/**
 * Model
 */
class Hstructure extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_hstructures';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    function afterDelete()
    {
        DB::table('zen_dolphin_hstructures_pivot')
            ->where('structure_id', $this->id)
            ->delete();
    }
}
