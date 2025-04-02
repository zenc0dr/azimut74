<?php namespace Mcmraak\Sans\Models;

use Model;
use October\Rain\Exception\ApplicationException;
use DB;
use Flash;

/**
 * Model
 */
class Group extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\NestedTree;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var array Validation rules
     */
    public $rules = [
        'slug' => 'required|unique:mcmraak_sans_groups'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_groups';

    /* Relations */

    public $hasMany = [
        'resorts' => [
            'Mcmraak\Sans\Models\Resort',
            'key' => 'group_id',
            'order' => ['name', 'sort_order'],
        ],
        'groups' => [
            'Mcmraak\Sans\Models\Group',
            'key' => 'parent_id'
        ],
    ];

    public $belongsTo = [
        'parent' => [
            'Mcmraak\Sans\Models\Group',
            'key' => 'parent_id'
        ],
    ];

    public function getFirstPageAttribute()
    {
        $resort = Resort::where('id', $this->default_resort_id)->first();
        $firstPage = DB::table('mcmraak_sans_pages as pages')
            ->leftJoin(
                'mcmraak_sans_wraps as wraps',
                'pages.id',
                '=',
                'wraps.page_id'
            )
            ->where('pages.resort_id', $resort->id)
            ->where('wraps.page_id', NULL)
            ->orderBy('pages.sort_order')
            ->first();

        if(!$firstPage) return false;

        return Page::find($firstPage->id);
    }

    public function getDefaultResortIdOptions()
    {
        return [0 => '<span class="noparent">- -</span>'] + Resort::where('group_id', $this->id)->lists('name', 'id');
    }

    public function setDefaultResortIdAttribute($value)
    {
        if(!$value && $this->nest_depth != 0)
            throw new ApplicationException('Это не страна. Должен быть указан курорт по умолчанию');
        $this->attributes['default_resort_id'] = $value;
        if($this->nest_depth == 0) Flash::success('Страна установлена');
    }

    public function getParentIdOptions() {
        if(!Group::count()) return [0 => '- -'];
        $groups = Group::orderBy('nest_left')->get();
        $return[0] = '- -';
        foreach ($groups as $group){
            $return[$group->id] = $this->nestItemDecor($group).$group->name;
        }
        return $return;
    }

    public function nestItemDecor($group)
    {
        $nest_depth = $group->nest_depth;
        if(!$nest_depth) return;
        $separator = '';
        for($i=0;$i<$nest_depth;$i++){
            $separator .= '- ';
        }
        return $separator;
    }

    public function setParentIdAttribute($value) {

        # Create root
        # Save root
        if(!isset($this->parent_id) && $this->parent_id == 0 && $value == 0){
            $this->attributes['parent_id'] = 0;
        }

        # Move node to root
        if($this->parent_id > 0 && $value == 0){
            $this->makeRoot();
        }

        # Move root to node
        # Move node to node
        if($this->parent_id >= 0 && $value > 0){
            $this->attributes['parent_id'] = $value;
        }
    }

}
