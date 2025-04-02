<?php namespace Zen\Dolphin\Models;

use Carbon\Carbon;
use Model;
use DB;
use Zen\Dolphin\Models\Log as Dlog;
use Zen\Dolphin\Traits\MultiGenerator;
use Zen\Dolphin\Classes\Core;

/**
 * Page - Посадочная страница
 *
 * Найти по url - $page = Page::getByUrl('site.ru/catalog/foo/bar/baz');
 *          или - $page = Page::getByUrl('foo/bar/baz');
 *
 * Получить url - $page->getUrl('domain:/extours/')
 *
 */
class Page extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\NestedTree;
    use MultiGenerator;

    public $table = 'zen_dolphin_pages';

    public $rules = [
        'slug' => 'required|unique:zen_dolphin_pages',
        'name' => 'required'
    ];

    public $hasMany = [
        'items' => [
            self::class,
            'key' => 'parent_id'
        ],
    ];

    public $belongsTo = [
        'parent' => [
            self::class,
            'key' => 'parent_id'
        ],
        'gallery' => [
            PageGallery::class,
            'key' => 'gallery_id'
        ],
        'reviews' => [
            PageReview::class,
            'key' => 'review_id'
        ],
        'features' => [
            Feature::class,
            'key' => 'feature_id'
        ],
        'page_block' => [
            PageBlock::class,
            'key' => 'pageblock_id'
        ],
    ];

    public $attachOne = [
        'preview_image' => [
            'System\Models\File',
            'delete' => true
        ],
    ];

    public $belongsToMany = [
        'groups' => [
            'Zen\Dolphin\Models\PageGroup', # Другая модель с которой мы устанавливаем связь через сводную таблицу
            'table'    => 'zen_dolphin_page_group_pivot', # Сводная таблица
            'key'      => 'page_id', # Ключ в сводной таблице отображающий id этой модели
            'otherKey' => 'group_id', # Ключ в сводной таблице отображающий id  другой модели
        ]
    ];

    public function getScopeAttribute()
    {
        $page_block = $this->page_block;
        if (!$page_block) {
            return null;
        }

        $type_id = $page_block->type_id;
        return ($type_id) ? 'АТМ' : 'ЭКСТ';
    }

    public function getScopeCodeAttribute()
    {
        $page_block = $this->page_block;
        if (!$page_block) {
            return null;
        }

        $type_id = $page_block->type_id;
        return ($type_id) ? 'atm' : 'ext';
    }

    public function getParentIdOptions()
    {
        $parents = self::orderBy('nest_left')->get();

        $options = [
            0 => '- Не выбрано -'
        ];

        foreach ($parents as $parent) {
            $options[$parent->id] = str_repeat("- ", $parent->nest_depth) . $parent->name;
        }

        return $options;
    }

    public function getGalleryIdOptions()
    {
        return [' -- '] + PageGallery::lists('name', 'id');
    }

    public function tinyboxGallery()
    {
        $gallery = $this->gallery;
        if (!$gallery) {
            return [];
        }

        $images = $gallery->images ?? null;
        if (!$images) {
            return [];
        }
        $core = new Core();
        $images = $images->map(function ($image) use ($core) {
            return  [
                'src' => $core->resize($image, ['width' => 1200]),
                'caption' => $image->title,
            ];
        })->toArray();
        return [
            'images' => $images,
            'title' => $gallery->title
        ];
    }

    public function getFeatureIdOptions()
    {
        return [' -- '] + Feature::lists('name', 'id');
    }

    public function getFeatures()
    {
        $features = $this->features;

        $images = $features->images ?? null;
        if (!$images) {
            return null;
        }
        $core = new Core();
        $images = $images->map(function ($feature) use ($core) {
            return [
                'image' => $core->resize($feature, ['width' => 500]),
                'title' => $feature->title,
                'desc' => $feature->description
            ];
        })->toArray();
        return [
            'title' => $features->title,
            'sub_title' => $features->sub_title,
            'images' => $images
        ];
    }

    public function getReviewIdOptions()
    {
        return [' -- '] + PageReview::lists('name', 'id');
    }

    public function getReviews($json = false)
    {
        $core = new Core();

        $reviews = $this->reviews;
        if (!$reviews || !$reviews->data) {
            return null;
        }

        $data = collect($reviews->data)->map(function ($item) use ($core) {
            $item['avatar'] = $core->resize(base_path('storage/app/media' . $item['avatar']));
            $item['image'] = $core->resize(base_path('storage/app/media' . $item['image']));

            #todo: Фикс, удалить после миграции
            if (isset($item['source_link'])) {
                $item['link'] = $item['source_link'];
                unset($item['source_link']);
            }
            $date_time = Carbon::parse($item['date_time']);
            $item['date'] = $date_time->format('d.m.Y');
            $item['time'] = $date_time->format('H:i');
            unset($item['date_time']);
            return $item;
        })->toArray();

        if ($json) {
            return json_encode($data, 256);
        }

        return $data;
    }

    private $route_chain = [];

    private function buildRouteChain($item)
    {
        $this->route_chain[] = [
            'slug' => $item->slug,
            'name' => $item->name,
        ];

        if ($item->parent) {
            $this->buildRouteChain($item->parent);
        }
    }

    public function getRouteChain()
    {
        if ($this->route_chain) {
            return $this->route_chain;
        }
        $this->buildRouteChain($this);
        return array_reverse($this->route_chain);
    }

    public function getLabelAttribute($value)
    {
        if (!$this->id) {
            return null;
        }
        return $value ?? $this->name;
    }

    public function getUrlChain()
    {
        return join('/', collect($this->getRouteChain())->pluck('slug')->toArray());
    }

    public function getUrl($before = '/')
    {
        if (strpos($before, 'domain:') === 0) {
            $before = str_replace('domain:', env('APP_URL'), $before);
        } else {
            $before = '/' . $before;
        }

        $url = $before . '/' . $this->getUrlChain();
        $url = preg_replace('/\/+/', '/', $url);

        return $url;
    }

    public static function getByUrl($url)
    {
        $slugs = explode('/', $url);
        $slug = $slugs[array_key_last($slugs)];
        $page = self::where('slug', $slug)->first();

        if (!$page) {
            return null;
        }

        if (strpos($url, $page->getUrlChain()) === false) {
            return null;
        }

        return $page;
    }

    public function getPageblockIdOptions()
    {
        $page_blocks = PageBlock::get();

        if (!$page_blocks) {
            return;
        }

        $output = [];

        foreach ($page_blocks as $page_block) {
            $type = ($page_block->type_id) ? 'АТМ' : 'ЭКСТ';
            $output[$page_block->id] = "{$page_block->name} [ $type ]";
        }

        return $output;
    }

    public $this_key = 'page_id';
    public $PageGroupDump;

    public function getPageGroupsOptions()
    {
        return $this->optionsMultiGenerator(PageGroup::class);
    }

    public function getPageGroupsAttribute()
    {
        return $this->getMultiGenerator('zen_dolphin_page_group_pivot', 'group_id');
    }

    public function setPageGroupsAttribute($value)
    {
        $this->setMultiGenerator('PageGroup', $value);
    }

    public function afterSave()
    {
        if ($this->parent_id) {
            DB::table($this->table)
                ->where('parent_id', $this->parent_id)
                ->update([
                    'pageblock_id' => $this->pageblock_id
                ]);
            DB::table($this->table)
                ->where('id', $this->parent_id)
                ->update([
                    'pageblock_id' => $this->pageblock_id
                ]);
        } else {
            DB::table($this->table)
                ->where('parent_id', $this->id)
                ->update([
                    'pageblock_id' => $this->pageblock_id
                ]);
        }

        $generator_options = [
            [
                'model' => 'PageGroup',
                'pivot' => 'zen_dolphin_page_group_pivot',
                'key' => 'group_id'
            ],
        ];

        $this->saveMultiGenerator($generator_options);
    }

    public function afterDelete()
    {
        DB::table('zen_dolphin_seoitems')->where('page_id', $this->id)->delete();
    }


}
