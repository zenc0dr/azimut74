<?php namespace Mcmraak\Sans\Models;

use Model;
use DB;
use Log;

/**
 * Model
 */
class Article extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var array Validation rules
     */
    public $rules = [
        'slug' => 'required|unique:mcmraak_sans_pages',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_articles';

    public $belongsTo = [
        'root' => ['Mcmraak\Sans\Models\Root'],
    ];

    public $attachMany = [
        'images' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ],
    ];

    public $hasOne = [
        'group' => [
            'Mcmraak\Sans\Models\Group',
            'key' => 'id',
            'otherKey' => 'group_id',
        ],
    ];

    public function getResortGroupOptions()
    {
        $roots = Root::get();

        $return = [];
        foreach ($roots as $root)
        {
            foreach ($root->resortgroups as $group)
            {
                $return[$root->id.':'.$group->id] = "[{$root->name}] -> " . $group->name;
            }
        }

        return $return;
    }

    public function setResortGroupAttribute($value)
    {
        $value = explode(':', $value);
        $this->attributes['root_id'] = intval($value[0]);
        $this->attributes['group_id'] = intval($value[1]);
    }
    public function getResortGroupAttribute()
    {
        return $this->root_id .':'.$this->group_id;
    }

    public function getSeoArticlesAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setSeoArticlesAttribute($value)
    {
        $this->attributes['seo_articles'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }

    public function resortGroups()
    {
        \DB::unprepared("SET sql_mode = ''");
        return DB::table('mcmraak_sans_pages as pages')
            ->where('rg.root_id', $this->root_id)
            ->where('groups.default_resort_id', '<>', 0)
            ->join(
                'mcmraak_sans_resorts as resorts',
                'resorts.id',
                '=',
                'pages.resort_id'
            )
            ->join(
                'mcmraak_sans_groups as groups',
                'groups.id',
                '=',
                'resorts.group_id'
            )
            ->join(
                'mcmraak_sans_roots_groups as rg',
                'rg.group_id',
                '=',
                'groups.id'
            )
            ->groupBy('groups.name')
            ->select(
                'groups.id as id',
                'groups.name as name',
                'groups.slug as slug'
            )->orderBy(
                'rg.sort_order'
            )->get();
    }

    public function articles()
    {
        $group = Group::find($this->group_id);
        $groups_ids = $group->getParentsAndSelf()->pluck('id')->toArray();
        return Article::whereIn('group_id', $groups_ids)
            ->where('active', 1)
            ->orderBy('sort_order')
            ->get();
    }

    public function resorts()
    {
        $group = Group::find($this->group_id);

        $groups_ids = $group
            ->allChildren()
            ->get()
            ->pluck('id')
            ->toArray();
        $groups_ids[] = $group->id;
        asort($groups_ids);
        $groups_ids = array_values($groups_ids);

        $resorts_ids = Resort::whereIn('group_id', $groups_ids)
            ->get()
            ->pluck('id')
            ->toArray();

        $pages = DB::table('mcmraak_sans_wraps as wrap')
            ->whereIn('wrap.resort_id', $resorts_ids)
            ->join(
                'mcmraak_sans_pages as page',
                'page.id',
                '=',
                'wrap.page_id'
            )
            ->join(
                'mcmraak_sans_resorts as resort',
                'resort.id',
                '=',
                'page.resort_id'
            )
            ->orderBy('page.sort_order')
            ->select(
                'resort.id as resort_id',
                'page.sort_order as sort_order',
                'page.slug as slug',
                'resort.name as name'
            )
            ->get();

        $unique = [];
        foreach ($pages as $page)
        {
            $resort_id = $page->resort_id;
            $name = $page->name;
            $sort_order = $page->sort_order;
            $slug = $page->slug;

            if(isset($unique[$resort_id]))
            {
                if($sort_order < $unique[$resort_id]['sort_order'])
                {
                    $unique[$resort_id] = [
                        'name' => $name,
                        'sort_order' => $sort_order,
                        'slug' => $slug
                    ];
                }
            } else {
                $unique[$resort_id] = [
                    'name' => $name,
                    'sort_order' => $sort_order,
                    'slug' => $slug
                ];
            }

        }

        return $unique;
    }
}
