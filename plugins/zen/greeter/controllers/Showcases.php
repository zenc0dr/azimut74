<?php namespace Zen\Greeter\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Cache;
use Zen\Greeter\Models\Showcase;

class Showcases extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
        'Backend\Behaviors\ReorderController'
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'zen.greeter.main'
    ];

    private static string $cache_key = 'greeter.showcases';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Greeter', 'greeter-main');
    }

    public static function createCache()
    {
        $base = Showcase::where('active', 1)
            ->select('url_entry', 'id')
            ->get()
            ->sort(function ($a, $b) {
                $end_star_a = preg_match('/\/\*$/', $a->url_entry);
                $end_star_b = preg_match('/\/\*$/', $b->url_entry);
                return $end_star_a > $end_star_b;
            })
            ->sort(function ($a, $b) {
                $slash_count_a = count(explode('/', str_replace('/*', '', $a->url_entry)));
                $slash_count_b = count(explode('/', str_replace('/*', '', $b->url_entry)));
                return $slash_count_a < $slash_count_b;
            })->values()->toArray();

        $base_refactor = [];
        foreach ($base as $item) {
            $base_refactor[$item['id']] = $item['url_entry'];
        }

        Cache::forever(self::$cache_key, $base_refactor);
        return $base_refactor;
    }

    public static function getCache()
    {
        $cache = Cache::get(self::$cache_key);
        if (!$cache) {
            $cache = self::createCache();
        }
        return $cache;
    }
}
