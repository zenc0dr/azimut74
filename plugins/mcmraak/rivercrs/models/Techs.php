<?php namespace Mcmraak\Rivercrs\Models;

use Model;

/**
 * Model
 */
class Techs extends Model
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
    public $table = 'mcmraak_rivercrs_techs';

    public function afterDelete()
    {
         $id = $this->id;
         # Delete "OnBoard records"
         TechsPivot::where('tech_id', $id)->delete();
    }

}