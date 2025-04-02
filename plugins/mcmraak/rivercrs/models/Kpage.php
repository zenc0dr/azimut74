<?php namespace Mcmraak\Rivercrs\Models;

use Carbon\Carbon;
use Model;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sortable;
use Zen\Grinder\Classes\Grinder;

/**
 * Model
 */
class Kpage extends Model
{
    use Validation;
    use Sortable;

    public $timestamps = false;

    public $table = 'mcmraak_rivercrs_kpages';

    ### Rules ###
    public $rules = [
        'slug' => 'required|unique:mcmraak_rivercrs_kpages'
    ];

    ### Relations ###
    public $belongsTo = [
        'type' => [Ktype::class, 'order' => 'sort_order', 'key' => 'ktype_id'],
    ];

    public $attachOne = [
        'image' => 'System\Models\File'
    ];

    ### Scopes ###
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeInMenu($query)
    {
        return $query->where('in_menu', 1);
    }

    function getKtypeIdOptions()
    {
        return Ktype::lists('name', 'id');
    }

    function getTown1Options()
    {
        return [0 => 'Не выбрано'] + Towns::lists('name', 'id');
    }

    function getTown2Options()
    {
        return [0 => 'Не выбрано'] + Towns::lists('name', 'id');
    }

    function getShipIdOptions()
    {
        return [0 => ' -- '] + Motorships::lists('name', 'id');
    }

    function getPicAttribute()
    {
        $image = $this->image;
        if(!$image) {
            return '/plugins/mcmraak/rivercrs/assets/images/no_image.png';
        }
        return Grinder::open($image)->getThumb();
    }

    function getMenuNameAttribute()
    {
        return ($this->menu_item) ?: $this->name;
    }

    private $presetDump = [];

    function setDaysAttribute($value) {
        $this->presetDump['days'] = intval($value);
    }

    function getDaysAttribute() {
        return @$this->presetDump['days'];
    }

    function setTown1Attribute($value) {
        $this->presetDump['town1'] = intval($value);
    }

    function getTown1Attribute() {
        return @$this->presetDump['town1'];
    }

    function setTown2Attribute($value) {
        $this->presetDump['town2'] = intval($value);
    }

    function getTown2Attribute() {
        return @$this->presetDump['town2'];
    }

    function setDate1Attribute($value)
    {
        $this->presetDump['date1'] = $value;
    }

    function getDate1Attribute() {
        return @$this->presetDump['date1'];
    }

    function setDate2Attribute($value)
    {
        $this->presetDump['date2'] = $value;
    }

    function getDate2Attribute() {
        return @$this->presetDump['date2'];
    }

    function setShipIdAttribute($value)
    {
        $this->presetDump['ship_id'] = $value;
    }

    function getShipIdAttribute() {
        return @$this->presetDump['ship_id'];
    }

    function getPreset()
    {
        $preset = $this->preset;
        if($preset == '{"town1":0,"town2":0,"date1":null,"date2":null,"days":1,"ship_id":"0"}') return;
        return $preset;
    }

    function getQueryAttribute()
    {
        $preset = $this->getPreset();
        if(!$preset) return;
        $preset = json_decode($preset, true);

        $query = [];
        if($preset['date1'])   $query['d1'] = Carbon::parse($preset['date1'])->format('d.m.Y');
        if($preset['date2'])   $query['d2'] = Carbon::parse($preset['date2'])->format('d.m.Y');
        if($preset['town1'])   $query['t1'] = [intval($preset['town1'])];
        if($preset['town2'])   $query['t2'] = [intval($preset['town2'])];
        if($preset['days'])    $query['ds'] = [intval($preset['days'])];
        if($preset['ship_id']) $query['sp'] = [intval($preset['ship_id'])];

        $query = json_encode($query, JSON_UNESCAPED_UNICODE);
        return $query;
    }

    function setSliderAttribute($value)
    {
        $this->attributes['slider'] = ($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : null;
    }

    function getSliderAttribute($value)
    {
        if(!$value) return;
        return json_decode($value, true);
    }

    ### Events ###

    function afterFetch()
    {
        $this->presetDump = json_decode($this->preset, true);
    }

    function beforeSave()
    {
        $this->attributes['preset'] = json_encode($this->presetDump, JSON_UNESCAPED_UNICODE);
    }

}
