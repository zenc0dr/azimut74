<?php namespace Mcmraak\Sans\Models;

use Model;

/**
 * Model
 */
class Review extends Model
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
    public $table = 'mcmraak_sans_reviews';

    public $belongsTo = [
        'hotel' => ['Mcmraak\Sans\Models\Hotel'],
    ];

    public $attachMany = [
        'images' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ],
    ];

    public $dataJson;

    public function afterFetch(){
        $this->dataJson = json_decode($this->data, true);
    }

    public function getNameAttribute()
    {
        if(!isset($this->dataJson['name'])) return '';
        return $this->dataJson['name'];
    }
    public function setNameAttribute($value)
    {
        $this->dataJson['name'] = $value;
    }

    public function getTownAttribute()
    {
        if(!isset($this->dataJson['town'])) return '';
        return $this->dataJson['town'];
    }
    public function setTownAttribute($value)
    {
        $this->dataJson['town'] = $value;
    }

    public function getEmailAttribute()
    {
        if(!isset($this->dataJson['email'])) return '';
        return $this->dataJson['email'];
    }
    public function setEmailAttribute($value)
    {
        $this->dataJson['email'] = $value;
    }

    public function getCommentAttribute()
    {
        if(!isset($this->dataJson['comment'])) return '';
        return $this->dataJson['comment'];
    }
    public function setCommentAttribute($value)
    {
        $this->dataJson['comment'] = $value;
    }

    public function getLikedAttribute()
    {
        if(!isset($this->dataJson['liked'])) return '';
        return $this->dataJson['liked'];
    }
    public function setLikedAttribute($value)
    {
        $this->dataJson['liked'] = $value;
    }

    public function getStartdoingAttribute()
    {
        if(!isset($this->dataJson['startdoing'])) return '';
        return $this->dataJson['startdoing'];
    }
    public function setStartdoingAttribute($value)
    {
        $this->dataJson['startdoing'] = $value;
    }

    public function getStopdoingAttribute()
    {
        if(!isset($this->dataJson['stopdoing'])) return '';
        return $this->dataJson['stopdoing'];
    }
    public function setStopdoingAttribute($value)
    {
        $this->dataJson['stopdoing'] = $value;
    }

    public function getDoingAttribute()
    {
        if(!isset($this->dataJson['doing'])) return '';
        return $this->dataJson['doing'];
    }
    public function setDoingAttribute($value)
    {
        $this->dataJson['doing'] = $value;
    }

    function beforeSave()
    {
        $this->attributes['data'] = json_encode ($this->dataJson, JSON_UNESCAPED_UNICODE);
    }

}
