<?php namespace Mcmraak\Sans\Models;

use Model;

/**
 * Model
 */
class Resort extends Model
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

    protected $fillable = [
        'id',
        'cid',
        'name',
    ];

    public $belongsTo = [
        'country' => [
            'Mcmraak\Sans\Models\Country',
            'key' => 'country_id'
        ],
        'group' => [
            'Mcmraak\Sans\Models\Group',
            'key' => 'group_id'
        ],
    ];



    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_resorts';

    /* Country filter */
    public function scopeFilterCountries($query, $id)
    {
        return $query->whereHas('country', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }

    /* Group filter */
    public function scopeFilterGroups($query, $id)
    {
        return $query->whereHas('group', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }

    /* Dropgown category */
    public function getGroupIdOptions() {
        $groups = new Group;
        return $groups->getParentIdOptions();
    }


}