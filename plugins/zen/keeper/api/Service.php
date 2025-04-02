<?php namespace Zen\Keeper\Api;

use Input;
use Zen\Keeper\Models\Settings;
use Illuminate\Filesystem\Filesystem;
use Log;
use October\Rain\Filesystem\Zip;

class Service
{

    public function upgrade()
    {
        $security_token = Input::get('security_token');

        if ($security_token !== Settings::get('security_token')) {
            return;
        }

        $fileSystem = new Filesystem;
        $new_version = Input::get('new_version');
        $plugin_path = base_path('plugins/zen/keeper');
        $temp_path = temp_path('keeper_upgrade');

        if (!file_exists($temp_path)) {
            mkdir($temp_path, 0777);
        }

        $new_version_path = $temp_path . '/new_version.zip';
        file_put_contents($new_version_path, $new_version);
        $fileSystem->deleteDirectory($plugin_path, true);
        Zip::extract($new_version_path, $plugin_path);
        echo 'upgraded!';
    }
}
