<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use Mcmraak\Rivercrs\Models\Motorships as Ship;

/**
 * Model
 */
class Discount extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_discounts';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public function getMotorshipsOptions()
    {
        return Ship::orderBy('name')->lists('name', 'id');
    }

    public function setMotorshipsAttribute($value)
    {
        if (!$value) {
            $this->attributes['motorships'] = null;
            return;
        }
        $this->attributes['motorships'] = '#' . join('#', $value) . '#';
    }

    public function getMotorshipsAttribute($value)
    {
        if (!$value) {
            return null;
        }
        $value = substr($value, 1);
        $value = substr($value, 0, -1);
        return explode('#', $value);
    }

    public function setOverlapActivationAttribute($value)
    {
        $this->attributes['overlap_activation'] = $value ?? 0;
    }
}
