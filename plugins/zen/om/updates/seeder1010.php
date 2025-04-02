<?php namespace Zen\Om\Updates;

use Seeder;

class Seeder1010 extends Seeder
{
    public function run()
    {
         //DB::unprepared(file_get_contents(base_path().'/plugins/zen/om/updates/goods.sql'));

        /*for($i=0;$i<100000;$i++)
        {
            \Zen\Om\Models\Item::insert([
                'name' => 'Item - '.$i,
                'slug' => 'item-'.$i,
                'category_id' => 1,
            ]);
        }*/
    }
}