<?php namespace Mcmraak\Sans\Models;

use Model;
use DB;

/**
 * Model
 */
class WrapType extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_wrap_types';

    function afterDelete()
    {
        DB::table('mcmraak_sans_wraps')->where('type_id', $this->id)->delete();
    }
}
