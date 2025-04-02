<?php namespace Mcmraak\Rivercrs\Models;

use Model;

/**
 * Model
 */
class Settings extends Model
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
    public $table = 'mcmraak_rivercrs_settings';

    /**
     * JSON =
     *      relinks_name
     *      relinks_link
     */
    public function getRelinksAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setRelinksAttribute($value)
    {
        $this->attributes['relinks'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }

    public function getBookingemailsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setBookingemailsAttribute($value)
    {
        $this->attributes['bookingemails'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }

    //reviewsemails
    public function getReviewsemailsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setReviewsemailsAttribute($value)
    {
        $this->attributes['reviewsemails'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }


    # Banks
    public $banksDump;

    public function getBanksText1Attribute()
    {
        if(isset($this->banksDump['banks_text1']))
        return  $this->banksDump['banks_text1'];
    }

    public function setBanksText1Attribute($value)
    {
        $this->banksDump['banks_text1'] = $value;
    }

    public function getBanksText2Attribute()
    {
        if(isset($this->banksDump['banks_text2']))
        return  $this->banksDump['banks_text2'];
    }

    public function setBanksText2Attribute($value)
    {
        $this->banksDump['banks_text2'] = $value;
    }

    public function getBanksAttribute()
    {
        if(isset($this->banksDump['banks'])) {
            return json_decode($this->banksDump['banks'], true);
        }
    }

    public function setBanksAttribute($value)
    {
        $this->banksDump['banks'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function afterFetch()
    {
        $this->banksDump = json_decode($this->attributes['banks'], true);
    }

    public function beforeSave()
    {
        $this->attributes['banks'] = json_encode($this->banksDump, JSON_UNESCAPED_UNICODE);
    }


}