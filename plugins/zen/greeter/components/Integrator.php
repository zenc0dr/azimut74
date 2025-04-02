<?php namespace Zen\Greeter\Components;

use Cms\Classes\ComponentBase;
use Zen\Greeter\Controllers\Showcases;
use Zen\Greeter\Models\Showcase;
use Cache;

class Integrator extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Встречающий баннер',
            'description' => 'Интеграция встречающего баннера.'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $showcases = Showcases::getCache();

        if (!$showcases) {
            return;
        }

        $path = request()->path();

        if ($path !== '/') {
            $path = '/' . $path;
        }

        $showcase_id = null;

        foreach ($showcases as $id => $url_entry) {
            if (substr($url_entry, -2) === '/*' && str_starts_with($path, substr($url_entry, 0, -2))) {
                $showcase_id = $id;
                break;
            }
            if ($url_entry === $path) {
                $showcase_id = $id;
                break;
            }
        }

        if (!$showcase_id) {
            return;
        }

        $showcase = Showcase::find($showcase_id);
        if (!$showcase) {
            return;
        }

        $this->page['showcase'] = $showcase;
    }
}
