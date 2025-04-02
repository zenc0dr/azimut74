<?php namespace Zen\Om;

use System\Classes\PluginBase;
use Yaml;
use File;
use Event;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Controllers\Users as UsersController;
use Zen\Om\Models\Profile as ProfileModel;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Zen\Om\Components\OmStatic'  => 'om_static',
            'Zen\Om\Components\OmRequest'  => 'om_request',
        ];
    }

    public function registerSettings()
    {

    }

    public function boot()
    {
        /*
         * Register menu items for the RainLab.Pages and RainLab.Sitemap plugin
         */
        Event::listen('pages.menuitem.listTypes', function () {
            return [
                'om-pages' => 'zen.om::lang.sitemap.all',
            ];
        });
        Event::listen('pages.menuitem.resolveItem', function ($type) {
            if ($type == 'url') {
                return [];
            }
            if ($type == 'om-pages' ) {
                $links = \Zen\Om\Controllers\Categories::getLinks();
                return $links;
            }
        });

        /* Extending RainLab.User plugin */

        UserModel::extend(function($model){
            $model->hasOne['profile'] = ['Zen\Om\Models\Profile'];
        });

        UsersController::extendFormFields(function($form, $model, $context){

            if(!$model instanceof UserModel) return;

            if(!$model->exists) return;

            ProfileModel::getFromUser($model);

            $configFile = plugins_path('zen/om/config/profile_fields.yaml');
            $config = Yaml::parse(File::get($configFile));
            $form->addTabFields($config);

        });
    }

    public function register()
    {
        $this->registerConsoleCommand('zen.urlcache', 'Zen\Om\Console\Urlcache');
        $this->registerConsoleCommand('zen.itemsseeder', 'Zen\Om\Console\Itemsseeder');
    }

}
