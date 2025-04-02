<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Classes\Ids;
use Mcmraak\Rivercrs\Classes\Parser;
use Mcmraak\Rivercrs\Models\Checkins;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use October\Rain\Support\Facades\Config;
use DB;
use Illuminate\Filesystem\Filesystem;

class Go extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'rivercrs:go';

    /**
     * @var string The console command description.
     */
    protected $description = 'Some action';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        #$this->joinTechsPivots('azimut-backup');
        #$this->deleteTechPivotDubles();
        #$this->joinOnboardPivots('azimut-backup');
        #$this->deleteOnboardPivotDubles();
        #$this->joinFiles();
        #$this->makeDiff();
        #$this->testVolgaVolga_1();
        #$this->wwCapture();
        #$this->getInfoflotShipNames();
        $this->checkCabinPivots();
    }

    function checkCabinPivots()
    {
        Config::set('database.connections.azimut_backup', array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => 3306,
            'database'  => 'azimut_backup',
            'username'  => 'zen',
            'password'  => 'zen',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ));

        $cabins = DB::table('mcmraak_rivercrs_cabins')->whereNotNull('infoflot_name')->get();
        $records_count = $cabins->count();
        $ussue_count = 0;
        foreach ($cabins as $cabin) {
            $now_cabin_pivots_count = DB::table('mcmraak_rivercrs_incabin_pivot')->where('cabin_id', $cabin->id)->count();
            $old_cabin_pivots_count = DB::connection('azimut_backup')->table('mcmraak_rivercrs_incabin_pivot')->where('cabin_id', $cabin->id)->count();

            if($now_cabin_pivots_count != $old_cabin_pivots_count) {
                $ussue_count++;
                echo "Cabin#{$cabin->id} [$now_cabin_pivots_count/$old_cabin_pivots_count]\n";
                $old_cabin_pivots = DB::connection('azimut_backup')->table('mcmraak_rivercrs_incabin_pivot')->where('cabin_id', $cabin->id)->get();
                foreach ($old_cabin_pivots as $pivot) {
                    DB::table('mcmraak_rivercrs_incabin_pivot')->insert((array) $pivot);
                }
            }
        }
        echo "Исправлено $ussue_count из $records_count\n";
    }

    function getInfoflotShipNames()
    {
        $idsDB = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));
        $ships = $idsDB->like('ship_page:');
        $ships_count = count($ships);
        $parser = new Parser;
        $i=0;

        $file_path = base_path('storage/infoflot_ships.txt');

        $out = '';
        while($i<$ships_count) {
            $i++;
            $ship = $parser->cacheWarmUp('infoflot2-ships', 'array', ['page'=> $i, 'limit' => 1], 7, 0, 0);
            $ship_id = $ship['data']['0']['id'];
            $ship_name = $ship['data']['0']['name'];
            $tours = $parser->cacheWarmUp('infoflot2-tours', 'array', ['ship' => $ship_id], 7, 0, 0);

            if(isset($tours['pagination']['records']['total'])) {
                $tours_count = intval($tours['pagination']['records']['total']);
            } else {
                $tours_count = 0;
            }

            $line = "$ship_name [$tours_count]\n";
            $out .= $line;
            echo $line;
        }
        file_put_contents($file_path, $out);
    }

    public function wwCapture()
    {
        Config::set('database.connections.azimut_backup', array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => 3306,
            'database'  => 'azimut_0508',
            'username'  => 'mcmraak',
            'password'  => '201345',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ));

        $backupDB = DB::connection('azimut_backup');

        $ww_checkins = $backupDB->table('mcmraak_rivercrs_checkins')
            ->where('eds_code', 'waterway')
            ->where('date', '>=', '2019-08-19 00:00:00')
            ->get();

        foreach ($ww_checkins as $ww_checkin) {

            $checkin = DB::table('mcmraak_rivercrs_checkins')->where('id', $ww_checkin->id)->first();

            if($checkin) continue;

            DB::table('mcmraak_rivercrs_checkins')->insert((array) $ww_checkin);

            $ww_prices = $backupDB->table('mcmraak_rivercrs_pricing')
                ->where('checkin_id', $ww_checkin->id)->get();

            foreach ($ww_prices as $ww_price) {
                $test = DB::table('mcmraak_rivercrs_pricing')
                    ->where('checkin_id', $ww_price->checkin_id)
                    ->where('cabin_id', $ww_price->cabin_id)
                    ->first();

                if($test) {
                    DB::table('mcmraak_rivercrs_pricing')
                        ->where('checkin_id', $ww_price->checkin_id)
                        ->where('cabin_id', $ww_price->cabin_id)
                        ->update((array) $ww_price);
                } else {
                    DB::table('mcmraak_rivercrs_pricing')->insert((array) $ww_price);
                }
            }

            echo "Перенесён заезд #{$ww_checkin->id} \n";
        }

    }


    public $techs_pivot;
    public $techs_added = 0;
    public function joinTechsPivots($db_name)
    {
        Config::set('database.connections.azimut_old', array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => 3306,
            'database'  => $db_name,
            'username'  => 'mcmraak',
            'password'  => '201345',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ));

        $pivot_A = DB::table('mcmraak_rivercrs_techs_pivot')->get();
        $pivot_B = DB::connection('azimut_old')->table('mcmraak_rivercrs_techs_pivot')->get();

        foreach ($pivot_A as $item) {
            $this->techs_pivot[] = [
                'motorship_id' => $item->motorship_id,
                'tech_id' => $item->tech_id,
                'value' => $item->value,
            ];
        }

        foreach ($pivot_B as $item) {
            $this->addUniTechRecord($item);
        }

        DB::table('mcmraak_rivercrs_techs_pivot')->truncate();

        foreach ($this->techs_pivot as $record) {
            DB::table('mcmraak_rivercrs_techs_pivot')->insert([
                'motorship_id' => $record['motorship_id'],
                'tech_id' => $record['tech_id'],
                'value' => $record['value'],
            ]);
        }

        echo "Items added: {$this->techs_added}\n";
    }

    # Добавить уникальную строку в переменную $this->techs_pivot
    public function addUniTechRecord($item)
    {
        $add = true;
        foreach ($this->techs_pivot as $record) {
            if(
                $record['motorship_id'] == $item->motorship_id &&
                $record['tech_id'] == $item->tech_id &&
                $record['value'] == $item->value
            ) {
                $add = false;
                break;
            }
        }

        if($add) {
            $this->techs_added++;
            $this->techs_pivot[] = [
                'motorship_id' => $item->motorship_id,
                'tech_id' => $item->tech_id,
                'value' => $item->value,
            ];
        }
    }

    public function deleteTechPivotDubles()
    {
        $pivot = DB::table('mcmraak_rivercrs_techs_pivot')->get();
        $count = 0;
        foreach ($pivot as $record) {

            # Найти все записи с такими параметрами
            $test = DB::table('mcmraak_rivercrs_techs_pivot')
                ->where('motorship_id', $record->motorship_id)
                ->where('tech_id', $record->tech_id)
                ->where('value', $record->value)
                ->count();

            #
            if($test>1){
                $count++;

                # Удалить все
                DB::table('mcmraak_rivercrs_techs_pivot')
                    ->where('motorship_id', $record->motorship_id)
                    ->where('tech_id', $record->tech_id)
                    ->where('value', $record->value)->delete();

                # Добавить одну
                DB::table('mcmraak_rivercrs_techs_pivot')->insert([
                    'motorship_id' => $record->motorship_id,
                    'tech_id' => $record->tech_id,
                    'value' => $record->value,
                ]);
            }
        }
        echo "Удалено: $count\n";
    }

    public $onboard_pivot;
    public $onboard_added = 0;
    public function joinOnboardPivots($db_name)
    {
        Config::set('database.connections.azimut_old', array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => 3306,
            'database'  => $db_name,
            'username'  => 'mcmraak',
            'password'  => '201345',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ));

        $pivot_A = DB::table('mcmraak_rivercrs_onboard_pivot')->get();
        $pivot_B = DB::connection('azimut_old')->table('mcmraak_rivercrs_onboard_pivot')->get();

        foreach ($pivot_A as $item) {
            $this->onboard_pivot[] = [
                'motorship_id' => $item->motorship_id,
                'onboard_id' => $item->onboard_id,
            ];
        }

        foreach ($pivot_B as $item) {
            $this->addUniOnboardRecord($item);
        }

        DB::table('mcmraak_rivercrs_onboard_pivot')->truncate();

        foreach ($this->onboard_pivot as $record) {
            DB::table('mcmraak_rivercrs_onboard_pivot')->insert([
                'motorship_id' => $record['motorship_id'],
                'onboard_id' => $record['onboard_id'],
            ]);
        }

        echo "Items added: {$this->onboard_added}\n";
    }

    public function addUniOnboardRecord($item)
    {
        $add = true;

        foreach ($this->onboard_pivot as $record) {

            if(
                $record['motorship_id'] == $item->motorship_id &&
                $record['onboard_id'] == $item->onboard_id
            ) {
                $add = false;
                break;
            }
        }

        if($add) {
            $this->onboard_added++;
            $this->onboard_pivot[] = [
                'motorship_id' => $item->motorship_id,
                'onboard_id' => $item->onboard_id,
            ];
        }
    }

    public function deleteOnboardPivotDubles()
    {
        $table = 'mcmraak_rivercrs_onboard_pivot';
        $pivot = DB::table($table)->get();
        $count = 0;
        foreach ($pivot as $record) {

            # Найти все записи с такими параметрами
            $test = DB::table($table)
                ->where('motorship_id', $record->motorship_id)
                ->where('onboard_id', $record->onboard_id)
                ->count();

            if($test>1){
                $count++;

                # Удалить все
                DB::table($table)
                    ->where('motorship_id', $record->motorship_id)
                    ->where('onboard_id', $record->onboard_id)
                    ->delete();

                # Добавить одну
                DB::table($table)->insert([
                    'motorship_id' => $record->motorship_id,
                    'onboard_id' => $record->onboard_id,
                ]);
            }
        }
        echo "Удалено: $count\n";
    }

    public function joinFiles(){
        Config::set('database.connections.azimut_29-12-2018', array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => 3306,
            'database'  => 'azimut_29-12-2018',
            'username'  => 'mcmraak',
            'password'  => '201345',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ));


        //$system_files_test = DB::table('system_files')->get();
        $system_files_prod = DB::connection('azimut_29-12-2018')->table('system_files')->get();

        //dd($system_files_prod->count());


        foreach ($system_files_prod as $file) {
            if(strpos($file->attachment_type, 'Mcmraak\Rivercrs')!=0) continue;
            $test = DB::table('system_files')->where([
                ['disk_name','=', $file->disk_name],
                ['file_name','=', $file->file_name],
                ['file_size','=', $file->file_size],
                ['content_type','=', $file->content_type],
                ['title','=', $file->title],
                ['description','=', $file->description],
                ['field','=', $file->field],
                ['attachment_type','=', $file->attachment_type],
                ['attachment_id','=', $file->attachment_id],
                ['is_public','=', $file->is_public],
            ])->first();

            if(!$test) {
                DB::table('system_files')->insert([
                    'disk_name' => $file->disk_name,
                    'file_name' => $file->file_name,
                    'file_size' => $file->file_size,
                    'content_type' => $file->content_type,
                    'title' => $file->title,
                    'description' => $file->description,
                    'field' => $file->field,
                    'attachment_type' => $file->attachment_type,
                    'attachment_id' => $file->attachment_id,
                    'is_public' => $file->is_public,
                    'sort_order' => $file->sort_order,
                    'created_at' => $file->created_at,
                ]);
                echo "Create attach record {$file->disk_name}\n";
            }
        }

    }

    public function getFileList($dir=null)
    {
        $fs = new Filesystem;
        $files = $fs->allFiles($dir, 1);
        $file_list = [];
        foreach ($files as $file) {
            $file_path = $file->getRelativePathname();
            //$rel_file_path = $dirname.'/'.$file_path;
            $full_file_path = $dir.'/'.$file_path;
            $file_list[] = $full_file_path;
        }
        return $file_list;
    }

    public function makeDiff(){

        $test_dir = '/storage/save/azimut/trans-29122018/test';
        $prod_dir = '/storage/save/azimut/trans-29122018/prod';
        $diff_dir = '/storage/save/azimut/trans-29122018/diff';

        $test_files = $this->getFileList($test_dir);
//        for($i=0;$i<10;$i++) {
//            //echo $test_files[$i]."\n";
//        }

        foreach ($test_files as $test_file) {

            $prod_path = str_replace($test_dir, $prod_dir, $test_file);
            $diff_path = str_replace($test_dir, $diff_dir, $test_file);
            if(!file_exists($prod_path)) {
                $this->copyFile($test_file, $diff_path);
                echo "copy: $test_file to $diff_path\n";
                if(!file_exists($diff_path)) die("Copy Error\n");
            }
        }

    }

    public function copyFile($of, $to)
    {
        if(!file_exists($to)) {
            if(!file_exists(dirname($to)))
                mkdir(dirname($to), 0777, true);
                copy($of, $to);
        }
    }

    public function testVolgaVolga_1()
    {
        $checkins = Checkins::where('eds_code', 'volga')->get();

        $count = $checkins->count();

        $ok = 0;
        $no = 0;

        $i = 0;
        foreach ($checkins as $checkin) {
            $table_data = \Mcmraak\Rivercrs\Controllers\VolgaSettings::getVolgaExcursion($checkin);
            if(!$table_data || !is_array($table_data) || count($table_data) < 2) {
                $no++;
                $i++;
                continue;
            };
            $ok++;
            echo "$i/$count = $ok/$no \r";
            $i++;
        }
        echo "\n";
    }

}
