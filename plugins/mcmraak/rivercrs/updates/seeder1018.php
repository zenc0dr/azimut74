<?php namespace Mcmraak\Rivercrs\Updates;

use October\Rain\Exception\ApplicationException;
use Seeder;
use DB;

class Seeder1018 extends Seeder
{
    public function run()
    {
        return; #Закомментировать для заполнения городами
    	
    	$townslist = file_get_contents(base_path().'/plugins/mcmraak/rivercrs/storage/townslist.txt');
    	$townslist = explode("\n", $townslist);
    	$towns_arr = [];
    	foreach ($townslist as $v)
    	{
    		$towns_arr[] = ['name' => $v];
    	}
    	
    	Db::table('mcmraak_rivercrs_towns')->insert($towns_arr);
    }
}