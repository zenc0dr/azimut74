<?php namespace Zen\Om\Models;

use Model;

/**
 * profile Model
 */
class Profile extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_om_profiles';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => ['RainLab\User\Models\User']
    ];

    public static function getFromUser($user)
    {
        if($user->profile) return $user->profile;

        $profile = new static;
        $profile->user = $user;
        $profile->save();

        $user->profile = $profile;

        return $profile;
    }
}
