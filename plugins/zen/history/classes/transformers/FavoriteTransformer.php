<?php namespace Zen\History\Classes\Transformers;



class FavoriteTransformer
{
   protected $preset;
   protected $type;

   public function __construct(array $preset)
   {
      $this->preset = $preset;
      $this->type = $this->preset['type_code'];
   }

   public function transformTypeData() {
      $result = $this->preset;
      switch ($this->type) {
         case 'tours':
            #$result = $this->transTour();
            break;
         case 'group-tours':
            #$result = $this->transGroupTour();
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

   public function transCruise()
   {
      $title = preg_replace("/(.*?)\(.*?\)\s?(.*?)/is", '\\1\\3', $this->preset['title']);  

      $data = [
         'url' => $this->preset['url'],
         'title' => $title,
         'inner_id' => $this->preset['element_id'],
         'days' => $this->preset['days'],
         'other' => $this->preset['other']
      ];
      return $data;
   }

}
