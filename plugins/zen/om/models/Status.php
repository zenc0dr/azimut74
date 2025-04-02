<?php namespace Zen\Om\Models;

use Model;
use October\Rain\Exception\ApplicationException as Exception;

/**
 * Model
 */
class Status extends Model
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
    public $table = 'zen_om_statuses';

    /* Relations */
    public $hasMany = [
        'orders' => [
            'Gymcrm\Club\Models\Order',
            'key' => 'status_id',
        ],
    ];


    public function setIdAttribute($value)
    {
        if(!$value) throw new Exception('Bad id!');
        if($this->id) {
            $status_id = self::find($value);
            if ($status_id)
                if($status_id->id != $this->id){
                    throw new Exception('This id exist! ');
            } else {
                    Order::where('status_id', $this->id)->update(['status_id'=> $value]);
                }
        }
        $this->attributes['id'] = $value;
    }

    public function afterDelete()
    {
        Order::where('status_id', $this->id)->update(['status_id'=>0]);
    }
}