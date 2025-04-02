<?php namespace Zen\Master;

use System\Classes\PluginBase;
use Session;
use Event;
use Cache;
use Illuminate\Contracts\Http\Kernel;
use Zen\Master\Middleware\ReplaceStorageLinks;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
        return [
            'options' => [
                'label'       => 'Чёрный список',
                'description' => 'Телефоны который вхожи в чёрный список, игнорируются системой отзывов и заявок',
                'icon'        => 'icon-ban',
                'permissions' => ['zen.master.black_list'],
                'class' => 'Zen\Master\Models\Settings',
                'order' => 500,
            ]
        ];
    }

    public function boot()
    {

        #$this->app[Kernel::class]->pushMiddleware(ReplaceStorageLinks::class);

        Event::listen('cms.router.beforeRoute', function () {
            $session_id = Session::getId();
            if (!app()->runningInConsole()) {
                $utm = array_merge(
                    [
                        'utm_source' => null,
                        'utm_medium' => null,
                        'utm_campaign' => null,
                        'utm_content' => null,
                        'utm_term' => null,
                        'yclid' => null,
                    ],
                    [
                        'utm_source' => $_GET['utm_source'] ?? null,
                        'utm_medium' => $_GET['utm_medium'] ?? null,
                        'utm_campaign' => $_GET['utm_campaign'] ?? null,
                        'utm_content' => $_GET['utm_content'] ?? null,
                        'utm_term' => $_GET['utm_term'] ?? null,
                        'yclid' => $_GET['yclid'] ?? null,
                    ]
                );

                if ($utm['utm_source']) {
                    master()->log(
                        "Фиксация utm данных (session:$session_id)",
                        $utm
                    );
                }

                Cache::add('utm_' . $session_id, $utm, 15);
            }
        });
    }

    public function register()
    {
        $this->registerConsoleCommand('master:check', 'Zen\Master\Console\CheckSystem');
    }
}
