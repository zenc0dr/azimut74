<?php namespace Srw\Core\Updates;

use Schema;
use Cms\Classes\Theme;
use Artisan;
use October\Rain\Database\Updates\Migration;
use October\Rain\Filesystem\Zip;

class BuilderTableCreateSrwCoreDomains extends Migration
{
    public function up()
    {

        $octoThemeZip = base_path().'/plugins/srw/core/theme/octosite.zip';

        if(!file_exists(base_path().'/themes/octosite')) {
            Zip::extract($octoThemeZip, base_path().'/themes/');
            Theme::setActiveTheme('octosite');
        }

        Schema::create('srw_core_domains', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('domain');
            $table->string('key');
        });
    }

    public function down()
    {
        Schema::dropIfExists('srw_core_domains');
    }
}