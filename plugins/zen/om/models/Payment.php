<?php namespace Zen\Om\Models;

use Model;

/**
 * Model
 */
class Payment extends Model
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
    public $table = 'zen_om_payments';

    /* Events */
    public function afterDelete()
    {
        Order::where('payment_id', $this->id)->update(['payment_id'=>0]);
    }
}