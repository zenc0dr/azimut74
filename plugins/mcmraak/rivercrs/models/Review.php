<?php namespace Mcmraak\Rivercrs\Models;

use Model;

/**
 * Model
 */
class Review extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_reviews';

    # Relationships
    public $belongsTo = [
        'motorship' => 'Mcmraak\Rivercrs\Models\Motorships',
    ];

    public $attachMany = [
        'files' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
    ];

    public $dataJson;

    public function afterFetch(){
        $this->dataJson = json_decode($this->data, true);
    }

    // $this->attributes['comfort_id'] = $value;

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

    public function getNotlikedAttribute()
    {
        if(!isset($this->dataJson['notliked'])) return '';
        return $this->dataJson['notliked'];
    }
    public function setNotlikedAttribute($value)
    {
        $this->dataJson['notliked'] = $value;
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