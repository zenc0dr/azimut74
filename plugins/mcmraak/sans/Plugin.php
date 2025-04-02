<?php namespace Mcmraak\Sans;

use System\Classes\PluginBase;
use Carbon\Carbon;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Mcmraak\Sans\Components\Search' => 'sans_search'
        ];
    }

    public function registerSettings()
    {
        return [
            'options' => [
                'label'       => 'Модуль SANS',
                'description' => 'Модуль для подбора отелей и санаториев',
                'icon'        => 'icon-building-o',
                'permissions' => ['mcmraak.sans'],
                'class' => 'Mcmraak\Sans\Models\Settings',
                'order' => 600,
            ]
        ];
    }

     public function registerMarkupTags()
    {
        return [
            'filters' => [
             
                'rusDate' => [$this, 'aDate'],
                'coincidence' => [$this, 'coincidence'],
                'splitArray' => [$this, 'splitArray'],
                'findAndDel' => [$this, 'findAndDelete'],
             
            ]
        ];
    }

   
    public function aDate($date, $nigth = null)
    {
       $months = [1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
       $dayOfWeek = ['вс', 'пн', 'вт', 'ср', 'чт','пт','сб'];
       $dates = Carbon::parse($date);

       if ($nigth) {
         $dates->addDays($nigth);   
       } 
       $m = $dates->month;
       $d = $dates->day;
       $day_week = $dates->dayOfWeek;
       $end = $d.' '.$months[$m].' ('.$dayOfWeek[$day_week].')';
       return $end;
    }

    public function coincidence($firstWord, $twoWord)
    {
    	$end =  similar_text ($firstWord, $twoWord, $present);
    	return $present;
    }


    public function splitArray($array, $params=null, $similarity=null) {
    	#$end = array_unique($array) 
 

    	#поиск уникальных данных
	   	$unique = [];
    	foreach($array as $item) {
    		array_push($unique, $item[$params]);
    	}
    	$unique = array_unique($unique);

    	#сбор массивов
    	$end = [];
    	foreach($unique as $current => $item) {
    		$arrays[$current] = array();
	 			foreach ($array as $key => $value) {
	 				if ($item == $value[$params]) {
 					  $arrays[$current][$key] = $value;
	 					unset($value);
	 				}
	 			}
	 			array_push($end, $arrays[$current]);
    	}
    	return $end;
    }
	
	# #srw преобразовываем данные в строчный вид и преобразовываем строки
    public function findAndDelete($text, $delete, $replace) {
		#dd($delete);
		$text = mb_convert_case($text, MB_CASE_LOWER);
		$delete = mb_convert_case($delete, MB_CASE_LOWER);
		$result = str_replace($delete, $replace, $text);
    	return $result;
    }

    public function register()
    {
        $this->registerConsoleCommand('sans:clear', 'Mcmraak\Sans\Console\Clear');
    }

}

