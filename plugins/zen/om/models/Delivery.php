<?php namespace Zen\Om\Models;

use Model;

/**
 * Model
 */
class Delivery extends Model
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
    public $table = 'zen_om_deliveries';

    /* Events */
    public function afterDelete()
    {
        Order::where('delivery_id', $this->id)->update(['delivery_id'=>0]);
    }
}