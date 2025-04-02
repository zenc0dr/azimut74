<?php namespace Zen\Om\Models;

use Model;

/**
 * Model
 */
class Setting extends Model
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
    public $table = 'zen_om_settings';

    public $settingsArr = [
        'item_htmlfix' => 0,
        'category_htmlfix' =>0,
    ];
    public $settingsDump = [
        'item_htmlfix' => 0,
        'category_htmlfix' =>0
    ];

    public function getItemHtmlfixAttribute()
    {
        return $this->settingsArr['item_htmlfix'];
    }
    public function setItemHtmlfixAttribute($value){
        $this->settingsArr['item_htmlfix'] = $value;
    }
    public function  getCategoryHtmlfixAttribute()
    {
        return $this->settingsArr['category_htmlfix'];
    }
    public function setCategoryHtmlfixAttribute($value){
        $this->settingsArr['category_htmlfix'] = $value;
    }
    public function getIdentifierAttribute()
    {
        return $this->settingsArr['identifier'];
    }
    public function setIdentifierAttribute($value){
        $this->settingsArr['identifier'] = $value;
    }

    /* Events */
    public function afterFetch()
    {
        if($this->settings)
        $this->settingsArr = $this->settingsDump = json_decode($this->settings, true);
    }
    public function beforeSave()
    {
        $this->attributes['settings'] = json_encode ($this->settingsArr, JSON_UNESCAPED_UNICODE);
    }

    public function afterSave()
    {
        if($this->settingsArr['category_htmlfix']!=$this->settingsDump['category_htmlfix'])
        {
            if($this->settingsArr['category_htmlfix']){
                \DB::unprepared("UPDATE zen_om_categories SET url_cache = CONCAT(url_cache, '.html');");
            } else {
                \DB::unprepared("UPDATE zen_om_categories SET url_cache = replace(url_cache, '.html', '') WHERE url_cache REGEXP '.html$';");
            }
        }
        if($this->settingsArr['item_htmlfix']!=$this->settingsDump['item_htmlfix'])
        {
            if($this->settingsArr['item_htmlfix']){
                \DB::unprepared("UPDATE zen_om_items SET url_cache = CONCAT(url_cache, '.html');");
            } else {
                \DB::unprepared("UPDATE zen_om_items SET url_cache = replace(url_cache, '.html', '') WHERE url_cache REGEXP '.html$';");
            }
        }
    }

    public function beforeDelete()
    {
        if($this->count() < 2)
            throw new ApplicationException('You can not delete a single store!');
    }

    public function afterDelete()
    {
        $profiles = Setting::where('active',1)->count();
        if(!$profiles){
            $min_id = Setting::min('id');
            $setting = Setting::find($min_id);
            $setting->active = 1;
            $setting->save();
        }
    }

    /* Query methods */
    public static function isItemHtmlfix()
    {
        $active_profile = self::where('active',1)->first();
        return $active_profile->item_htmlfix;
    }
    public static function isCategoryHtmlfix()
    {
        $active_profile = self::where('active',1)->first();
        return $active_profile->category_htmlfix;
    }

    public static function itemId()
    {
        $active_profile = self::where('active', 1)->first();
        return $active_profile->identifier;
    }

}