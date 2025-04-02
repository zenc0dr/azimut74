<?php namespace Mcmraak\Sans\Models;

use Model;
use DB;
use October\Rain\Exception\ApplicationException;
use Log;

/**
 * Model
 */
class Root extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var array Validation rules
     */
    public $rules = [
        'slug' => 'required|unique:mcmraak_sans_roots',
        'default_group_id' => 'required',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_roots';

    /* Relations */

    public $belongsToMany = [
        'resortgroups' => [
            'Mcmraak\Sans\Models\Group',
            'table'    => 'mcmraak_sans_roots_groups',
            'key'      => 'root_id',
            'otherKey' => 'group_id',
            'order' => 'sort_order',
        ]
    ];


    public function getDefaultGroupIdOptions(){
        return $this->getGroupIdOptions();
    }

    public function setDefaultGroupIdAttribute($value)
    {

        //Log::debug(print_r($this->groups, 1));

        if(!$value)
            throw new ApplicationException('Должна быть указана группа по умолчанию');
//        $test_group = DB::table('mcmraak_sans_roots_groups')
//            ->where('root_id', $this->id)
//            ->where('group_id',$value)
//            ->count();
//
//        if(!$test_group)
//            throw new ApplicationException('Группа по умолчанию отсутствует в списке связанных групп курортов');

        $this->attributes['default_group_id'] = $value;
    }


    public function getFirstPageAttribute()
    {
        $group = Group::where('id', $this->default_group_id)->first();
        $resort = Resort::where('id', $group->default_resort_id)->first();
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

    /* Resort groups repeater */

    /* Group dropdown */
    public $groupsDump;
    public $groupsNest;
    public function getGroupIdOptions()
    {
        $groups = new Group;
        return $groups->getParentIdOptions();
    }

    public function setGroupsAttribute($value)
    {
        $this->groupsDump = $value;
    }

    public function getGroupsAttribute()
    {
        $records = DB::table('mcmraak_sans_roots_groups')
            ->where('root_id', $this->id)->orderBy('sort_order')->get();
        $repeater = [];
        foreach ($records as $record) {
            $repeater[] = [
                'group_id' => $record->group_id,
            ];
        }
        return $repeater;
    }

    public function saveGroups()
    {
        if(!$this->groupsDump) return;
        DB::table('mcmraak_sans_roots_groups')->where('root_id', $this->id)->delete();
        $sort_order = 0;
        foreach($this->groupsDump as $record)
        {
            $arr[] =
                [
                    'root_id' => $this->id,
                    'group_id' => $record['group_id'],
                    'sort_order' => $sort_order,
                ];
            $sort_order++;
        }
        DB::table('mcmraak_sans_roots_groups')->insert($arr);
    }


    /* Events */

    public function afterSave()
    {
        $this->saveGroups();

        //Log::debug(print_r($this->default_group_id, 1));
        $this->checkDefaultGroup();

    }

    public function checkDefaultGroup()
    {
        if(!$this->default_group_id) return;

        $test_group = DB::table('mcmraak_sans_roots_groups')
            ->where('root_id', $this->id)
            ->where('group_id',$this->default_group_id)
            ->count();


        if(!$test_group) {
            DB::table('mcmraak_sans_roots')
                ->where('id', $this->id)
                ->update([
                    'default_group_id' => 0
                ]);
            throw new ApplicationException('Группа по умолчанию отсутствует в списке 
            связанных групп курортов');
        }



    }


    public function afterDelete()
    {
        DB::table('mcmraak_sans_roots_groups')->where('root_id', $this->id)->delete();
    }
}
