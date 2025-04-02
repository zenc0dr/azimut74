<?php namespace Mcmraak\Sans\Models;

use Model;
use Log;
use DB;
use Mcmraak\Sans\Models\Resort;
use Mcmraak\Sans\Models\Group;
use Input;
use Carbon\Carbon;


/**
 * Model
 */
class Page extends Model
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
        'slug' => 'required|unique:mcmraak_sans_pages',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_pages';

    /* Relations */

    public $attachMany = [
        'images' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ],
    ];

    public $hasOne = [
        'resort' => [
            'Mcmraak\Sans\Models\Resort',
            'key' => 'id',
            'otherKey' => 'resort_id',
        ],
    ];


    /* Filters */

    public function scopeFilterResorts($query, $model)
    {
        return $query->whereHas('resort', function($q) use ($model) {
            $q->whereIn('id', $model);
        });
    }


    /* Methods */
    public function getEventsAttribute()
    {
        \DB::unprepared("SET sql_mode = ''");
        $options = \DB::table('mcmraak_sans_wraps')
            ->where('page_id', $this->id)
            ->groupBy('name')
            ->orderBy('type_id')
            ->get()
            ->pluck('name')
            ->toArray();

        return '['.join('], [', $options).']';
    }

    public function getRootIdOptions()
    {
        return Root::lists('name','id');
    }

    public function getResortIdOptions()
    {
        return Resort::lists('name','id');
          
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
        $group = Resort::find($this->resort_id)->group;
        $groups_ids = $group->getParentsAndSelf()->pluck('id')->toArray();
        return Article::whereIn('group_id', $groups_ids)
            ->where('active', 1)
            ->orderBy('sort_order')
            ->get();
    }

    public function resorts()
    {
        //return [];

        # Имея такие параметры как: root_id и resort_id
        # нужно выяснить к какой группе относится resort_id
        # нужно получить все отели этоу группы и её подгрупп
        # Нужно получить список страниц Page с этими отелями

        $group = Resort::find($this->resort_id)->group;
        $parents = $group->getParents();
        if($parents->count() > 1) $group = $parents[1];

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

        #dd($pages);

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

    public function wraps($type_id)
    {
        DB::unprepared("SET sql_mode = ''");
        $wraps = DB::table('mcmraak_sans_wraps')
        ->where('type_id', $type_id)
        ->where('resort_id', $this->resort->id)
        ->groupBy('name')
        ->orderBy('name')
        ->get();

        # Это страница сейчас
        # $page = self::find($item->page_id);

        # Перечислим уникальные события одного типа
        $return = [];
        foreach ($wraps as $item)
        {

            # вот тут уникальное событие по времени, нужно проверить
            # существет ли страница с теми событиям которые уже присутствуют (других типов)
            # и с этим событием в комбинации ?

            $return[] = [
                'name' => $item->name,
                'slug' => self::typeUrl($type_id, $item->name, $this),
            ];

        }

        return $return;
    }

    public function typeUrl($type_id, $wrap_name, $page)
    {

        # Найдём все страницы c этим экземпляром
        $this_events = DB::table('mcmraak_sans_wraps')
            ->where('resort_id', $page->resort_id)
            ->where('type_id', $type_id)
            ->where('name', $wrap_name)->get();



        # С этим эвентом только одна страница, её и выводим
        if($this_events->count() == 1)
        {
            return self::find($this_events[0]->page_id)->slug;
        }
        else
        # С этим эвентом ещё есть страницы, возможны комбинации.
        {

            # Какие эвенты активны для этой страницы ?
            $this_page_active_events = DB::table('mcmraak_sans_wraps')
                ->where('page_id', $page->id)
                ->get();

            if($this_page_active_events->count())
            # Страница уже с активными эвентами
            {


                # Нужно проверить, существует ли этот эвент в комбинации с активными эвентами
                # Если существует, получаем страницу

                $combi_page = self::checkEventsCombination(
                    $this_page_active_events,
                    $page->resort_id,
                    $type_id,
                    $wrap_name
                );

                if(!$combi_page) {
                    // Не существует страницы в комбинации с активными эвентами
                    $page = self::getOptimalPage($page->resort_id, $type_id, $wrap_name, $this_page_active_events);
                } else {
                    $page = $combi_page;
                }

                # Если не существует, получаем его оптимальную страницу
                # return 'Страница с активными эвентами';
                return $page->slug;
            }
            else
            # Страница без активных эвентов
            {
                # Выводим оптимальную страницу для этого эвента
                $page = self::getOptimalPage($page->resort_id, $type_id, $wrap_name);
                return $page->slug;
            }
        }
    }

    public function OneEventToPage($resort_id, $event_name)
    {
        $pages_ids = DB::table('mcmraak_sans_wraps')
            ->where('resort_id', $resort_id)
            ->where('name', $event_name)
            ->pluck('page_id')
            ->toArray();
        foreach ($pages_ids as $page_id) {
            $pages_count = DB::table('mcmraak_sans_wraps')
                ->where('page_id', $page_id)->count();
            if($pages_count == 1) {
                return $page_id;
            }
        }
    }

    public function TwoEventsToPage($resort_id, $type_id, $event_1, $event_2)
    {
        $pages_ids_a = DB::table('mcmraak_sans_wraps')
            ->where('resort_id', $resort_id)
            ->where('type_id', $type_id)
            ->where('name', $event_1)
            ->pluck('page_id')
            ->toArray();
        $pages_ids_b = DB::table('mcmraak_sans_wraps')
            ->where('resort_id', $resort_id)
            ->where('type_id', '<>', $type_id)
            ->where('name', $event_2)
            ->pluck('page_id')
            ->toArray();

        foreach ($pages_ids_a as $page_id_a)
        {
            if(in_array($page_id_a,$pages_ids_b))
            {
                return $page_id_a;
            }
        }
    }

    public function getOptimalPage($resort_id, $type_id, $event_name, $old_events=null)
    {

        if($old_events && count($old_events) == 1)
        {

            $test_page = $this->TwoEventsToPage(
                $resort_id,
                $type_id,
                $old_events[0]->name,
                $event_name
            );

            if($test_page) {
                return self::find($test_page);
            } else {
                $one_event_pade_id = $this->OneEventToPage($resort_id, $event_name);
                if($one_event_pade_id) return self::find($one_event_pade_id);
            }
        }

        # Получим id-шники страниц с ЭТИМ эвентом
        $this_event_pages_ids = DB::table('mcmraak_sans_wraps')
            ->where('resort_id', $resort_id)
            ->where('type_id', $type_id)
            ->where('name', $event_name)
            ->pluck('page_id')
            ->toArray();

        # Если уже есть эвенты
        if($old_events)
        {
            $old_events_arr = [];
            foreach ($old_events as $item)
            {
                $old_events_arr[] = $item->name;
            }

            $best_id = [];
            foreach ($this_event_pages_ids as $page_id)
            {
                $count_pages = DB::table('mcmraak_sans_wraps')
                    ->where('page_id', $page_id)
                    ->where(function($q) use ($old_events_arr) {
                        foreach ($old_events_arr as $e) {
                            $q->orWhere('name', $e);
                        }
                    })
                    ->count();
                $best_id[$count_pages] = $page_id;
            }
            $optimal_page_id = $best_id[max(array_keys($best_id))];

            return self::find($optimal_page_id);
        }

        # Посчитаем эвенты для каждой страницы
        $events_count = 1000000;
        $optimal_page_id = false;
        foreach ($this_event_pages_ids as $page_id)
        {
            $page_events = DB::table('mcmraak_sans_wraps')
                ->where('page_id', $page_id)->count();

            if($page_events < $events_count) {
                $events_count = $page_events;
                $optimal_page_id = $page_id;
            }
        }

        return self::find($optimal_page_id);
    }

    public function checkEventsCombination($active_events, $resort_id, $type_id, $event_name)
    {

        # Получим id-шники страниц с этим эвентом
        $this_event_pages_ids = DB::table('mcmraak_sans_wraps')
            ->where('resort_id', $resort_id)
            ->where('type_id', $type_id)
            ->where('name', $event_name)->pluck('page_id')->toArray();

        foreach ($this_event_pages_ids as $page_id )
        {
            $allow = true;
            foreach ($active_events as $active_event)
            {
                $active_event_test = DB::table('mcmraak_sans_wraps')
                    ->where('page_id', $page_id)
                    ->where('resort_id', $resort_id)
                    ->where('type_id',$active_event->type_id)
                    ->where('name', $active_event->name)
                    ->first();
                if(!$active_event_test) {
                    $allow = false;
                    break;
                }
            }
            if($allow){
                return self::find($page_id);
            }
        }
    }

    public function getWiResortIdOptions()
    {
        return [0 => '- -']  + Resort::lists('name', 'id');
    }

    public function getWiResortIdGroupOptions()
    {
        return [0 => '- -']  + Group::lists('name', 'id');
    }


    public $wi_options;

    public function setWiResortIdGroupAttribute($val){
	   	if($this->is_show_group) {
					$this->wi_options['wi_resort_id_group'] =  $this->wi_options['wi_resort_id'] = 'group_id:'.$val;
			} else {
				$this->wi_options['wi_resort_id_group'] = 'group_id:'.$val;
			}
    }
    
    public function getWiResortIdGroupAttribute(){
    	if($this->is_show_group) {
        $value = str_replace('group_id:', '',$this->wi_options['wi_resort_id_group']);
        return $value;
      } else {
      	return 0;
      }
    }

    public function setWiResortIdAttribute($val){
    	if($this->is_show_group) {
    		$this->wi_options['wi_resort_id'] = $this->wi_options['wi_resort_id_group'];
    	} else {
    		 $this->wi_options['wi_resort_id'] = $val;
    	}
    }

    public function getWiResortIdAttribute(){
    	if($this->is_show_group) {
        $value = str_replace('group_id:', '',$this->wi_options['wi_resort_id']);
        return $value;
      } else {
      	return $this->wi_options['wi_resort_id'];
      }
    }


    public function setWiParentsCountAttribute($val){
        $this->wi_options['wi_parents_count'] = $val;
    }
    public function getWiParentsCountAttribute(){
        return $this->wi_options['wi_parents_count'];
    }

    public function setWiChildrensCountAttribute($val){
        $this->wi_options['wi_childrens_count'] = $val;
    }
    public function getWiChildrensCountAttribute(){
        return $this->wi_options['wi_childrens_count'];
    }

    public function setWiDateAttribute($val){
        if(!$val) $val = '';
        $this->wi_options['wi_date'] = $val;
    }
    public function getWiDateAttribute(){
        return $this->wi_options['wi_date'];
    }

    public function setWiDateDeltaDaysAttribute($val){
        $this->wi_options['wi_date_delta_days'] = $val;
    }
    public function getWiDateDeltaDaysAttribute(){
        return $this->wi_options['wi_date_delta_days'];
    }

    public function setWiDaysFromAttribute($val){
        $this->wi_options['wi_days_from'] = $val;
    }
    public function getWiDaysFromAttribute(){
        return $this->wi_options['wi_days_from'];
    }

    public function setWiDaysToAttribute($val){
        $this->wi_options['wi_days_to'] = $val;
    }
    public function getWiDaysToAttribute(){
        return $this->wi_options['wi_days_to'];
    }

    public function setWiSearchByHotelNameAttribute($val){
        $this->wi_options['wi_search_by_hotel_name'] = $val;
    }
    public function getWiSearchByHotelNameAttribute(){
        return $this->wi_options['wi_search_by_hotel_name'];
    }

    public function getSearchPresetAttribute($val)
    {
        $val = json_decode($val,1);
        if(Input::get('filter')){
            $this->attributes['search_preset_active'] = 1;
            $filter = explode('|', Input::get('filter'));
            if($filter[0]) $val['wi_resort_id'] = $filter[0];
            if($filter[1]) $val['wi_parents_count'] = $filter[1];
            if($filter[2]) $val['wi_childrens_count'] = $filter[2];

            if($filter[3]) {
                if(preg_match("/^\d+$/", $filter[3])){
                    $carbon = Carbon::now();
                    $carbon->addDays(intval($filter[3]));
                    $val['wi_date'] = $carbon->format('d.m.Y');
                } else {
                    $val['wi_date'] = $filter[3];
                }
            }

            if($filter[4]) $val['wi_date_delta_days'] = $filter[4];
            if($filter[5]) $val['wi_days_from'] = $filter[5];
            if($filter[6]) $val['wi_days_to'] = $filter[6];
            if($filter[7]) $val['wi_search_by_hotel_name'] = $filter[7];
        }

        return $val;
    }

    public $old_resort_id = false;

    /* Events */
    public function afterFetch()
    {
        $this->wi_options = $this->search_preset;
        $this->old_resort_id = $this->resort_id;
    }

    public function beforeSave()
    {
        $this->attributes['search_preset'] = json_encode($this->wi_options, JSON_UNESCAPED_UNICODE);
    }

    public function afterSave()
    {
        if($this->old_resort_id && $this->old_resort_id != $this->resort_id)
        {
            DB::table('mcmraak_sans_wraps')
                ->where('page_id', $this->id)
                ->where('resort_id', $this->old_resort_id)
                ->delete();
        }
    }

    public function afterDelete()
    {
        DB::table('mcmraak_sans_wraps')->where('page_id', $this->id)->delete();
    }

}
