<?php namespace Zen\Quiz\Updates;

use Seeder;
use DB;

class CitiesSeeder extends Seeder
{
    public function run()
    {
        $cities = [
            'Астрахань','Волгоград','Казань','Кострома','Москва','Н.Новгород','Пермь','Ростов-на-Дону',
            'Самара','С.Петербург','Сарапул','Саратов','Сочи','Ульяновск','Чайковский','Чебоксары',
            'Ярославль','Другое'
        ];
        
        $data_cities = [];
        
        foreach ($cities as $index=>$city) {
            $data_cities[] = [
              'name' => $city,
              'active' => 1,
              'sort_order' => $index+1
            ];
        }
        
        DB::table('zen_quiz_cities')->truncate();
        DB::table('zen_quiz_cities')->insert($data_cities);
    }
}