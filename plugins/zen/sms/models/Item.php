<?php namespace Zen\Sms\Models;

use Model;

/**
 * Model
 */
class Item extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_sms_items';

    public $belongsTo = [
        'profile' => [
            'Zen\Sms\Models\Profile',
            'key' => 'profile_id'
        ],
    ];

    public function getProfileIdOptions()
    {
        return Profile::lists('name', 'id');
    }
}
