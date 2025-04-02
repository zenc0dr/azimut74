<?php namespace Mcmraak\Rivercrs\Models;

use Model;

/**
 * Model
 */
class Onboard extends Model
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
    public $table = 'mcmraak_rivercrs_onboard';

    public function afterDelete()
    {
         $id = $this->id;
         # Delete "OnBoard records"
         OnboardPivot::where('onboard_id', $id)->delete();
    }

}