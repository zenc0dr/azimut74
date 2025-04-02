<?php namespace Zen\Ts;

use System\Classes\PluginBase;
use Event;
use Zen\Ts\Models\Settings;
use Exception;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
        return [
            'options' => [
                'label'       => 'Theme Switcher',
                'description' => 'Switching themes by route.',
                'icon'        => 'oc-icon-arrows-h',
                'permissions' => ['zen.ts.main'],
                'class' => 'Zen\Ts\Models\Settings',
                'order' => 600,
            ]
        ];
    }

    public function boot()
    {
        try {
            $active = Settings::get('active');
            if (!$active) {
                return;
            }

            $path = request()->path();
            $host = request()->getHost();

            if ($path !== '/') {
                $path = '/' . $path;
            }

            $theme = null;
            $routes = Settings::get('routes');

            foreach ($routes as $route) {
                $search_path = $path;
                $url_entry = $route['url_entry'];

                if (str_starts_with($url_entry, '{')) {
                    $search_path = '{' . $host . '}' . $search_path;
                }

                if (substr($url_entry, -2) === '/*' && str_starts_with($search_path, substr($url_entry, 0, -2))) {
                    $theme = $route['theme'];
                    break;
                }
                if ($url_entry === $search_path) {
                    $theme = $route['theme'];
                    break;
                }
            }

            if (!$theme) {
                return;
            }

            Event::listen(
                'cms.theme.getActiveTheme',
                function () use ($theme) {
                    return $theme;
                }
            );
        } catch (Exception $exception) {
            echo "<div style='padding:5px;text-align:center;color:red;font-size:10px'>{$exception->getMessage()}</div>";
        }

    }
}
