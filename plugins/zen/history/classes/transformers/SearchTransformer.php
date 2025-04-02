<?php namespace Zen\History\Classes\Transformers;





class SearchTransformer
{
   protected $preset;
   protected $type;

   public function __construct(string $preset)
   {
      $this->preset = json_decode($preset, true);
      $this->type = $this->preset['type'];
   }

   public function transformTypeData() {
      $result = null;
      switch ($this->type) {
         case 'tours':
            $result = $this->transTour();
            break;
         case 'group-tours':
            $result = $this->transGroupTour();
            break;
         case 'autobus-tours':
            //return $this->transAutoTour();
            break;
         case 'cruise':
            $result = $this->transCruise();
            break;
         case 'sans':
            //$result = $this->transSans();
            break;
      }

      return $result;
   }

   //Пример выходных данных трансформера
   //$data = [
   //   'name' => $this->preset['name'],
   //   'dates' => [$this->preset['date_of'], $this->preset['date_to']],
   //   'days' => $this->preset['days'],
   //   'geo' => $geo_points,
   //   'adults' => $this->preset['adults'],
   //   'childrens' => $childrens,
   //];

   public function transAutoTour() {
      $preset_str = $this->preset['preset'];
      $preset = explode(';', $preset_str);
      $format_preset = [];

      foreach ($preset as $param) {
         $temp = explode('=', $param);
         $format_preset[$temp[0]] = $temp[1];
      }

      $geo_objects = explode(',', $format_preset['g']);
      $f_geo_object = $geo_objects;

      $data = [
         'type' => 'autobus-tours',
         'dates' => explode(',', $format_preset['d']),
         'geo' => $f_geo_object
      ];

      return $data;
   }

   public function transTour() {

      $find_simbol = strripos($this->preset['name'], '>');
      if ($find_simbol === false) {
        $geo_temp = explode('Экскурсионные туры: Саратов ', $this->preset['name']);
      } else {
        $geo_temp = explode('Экскурсионные туры: Саратов > ', $this->preset['name']);
      }

      $geo_points = [];
      if (count($geo_temp) > 1) {
        $geo_points = explode(',', $geo_temp[1]);
        $geo_points = array_merge(['Саратов'], $geo_points);
      }

      $childrens = count($this->preset['childrens']);
      $data = [
         'type' => 'tours',
         'name' => $this->preset['name'],
         'dates' => [$this->preset['date_of'], $this->preset['date_to']],
         'days' => $this->preset['days'],
         'geo' => $geo_points,
         'adults' => $this->preset['adults'],
         'childrens' => $childrens,
      ];
      return $data;
   }

   public function transGroupTour()
   {
      $geo_points = explode(',', $this->preset['geo']);

      $data = [
         'type' => 'group-tours',
         'name' => $this->preset['title'],
         'days' => $this->preset['days'],
         'geo' => $geo_points,
      ];
      return $data;
   }

   public function transCruise()
   {
      $geo_temp = explode('Речные круизы: ', $this->preset['name']);
      $geo_points = explode(',', $geo_temp[1]);

      $dates = [];
      if ($this->preset['d1'] != NULL) {
         $dates[] = $this->preset['d1'];
      }

      if ($this->preset['d2'] != NULL) {
         $dates[] = $this->preset['d2'];
      }

      $data = [
         'type' => 'cruise',
         'name' => $this->preset['name'],
         'dates' => $dates,
         'days' => $this->preset['days'],
         'geo' => $geo_points,
         'city_path' => count($this->preset['t2']),
         'ship' => count($this->preset['ship'])
      ];
      return $data;
   }

   public function transSans()
   {
      $data = [
         'type' => 'sans',
         'name' => 'Отели и санатории',
         'dates' => [$this->preset['dateTo']],
         'days' => [$this->preset['nightsMin']],
         'adults' => $this->preset['adults'],
         'childrens' => $this->preset['kids'],

      ];
      return $data;
   }





}
