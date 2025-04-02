<?php namespace Mcmraak\Sans\Classes;

use Input;
use Validator;
use Log;
use Mcmraak\Sans\Models\Hotel;
use Mcmraak\Sans\Models\Review;
use System\Models\File;

class Reviews
{
    public $input;
    public $registred_actions = [
        'send'
    ];

    public function json($array){
        echo json_encode ($array, JSON_UNESCAPED_UNICODE);
    }

    public function testAction($action){
        if(in_array($action,$this->registred_actions)){
            return true;
        }
    }

    public static function api($action)
    {
        $sync = new self;
        if($sync->testAction($action)){
            $sync->input = Input::all();
            $sync->{$action}();
        };
    }

    public static function validation($data)
    {
        $return = [
            'messages' => [],
            'badfields' => [],
        ];
        $make_a = [];
        $make_b = [];
        foreach ($data as $key => $val)
        {
            $names = explode('|', $key);
            $make_a[$names[1]] = $val[0];
            $make_b[$names[1]] = $val[1];
        }

        $validator = Validator::make($make_a, $make_b);

        if ($validator->fails()){
            $messages = $validator->messages();
            foreach ($data as $key => $val){
                $names = explode('|', $key);
                if ($messages->has($names[1])) {
                    $return['messages'][] = $messages->first($names[1]);
                    $return['badfields'][$names[0]] = true;
                }
            }
        }
        return $return;
    }

    public function send()
    {

        $comment = trim($this->input['comment']);
        $liked = trim($this->input['liked']);
        $startdoing = trim($this->input['startdoing']);
        $stopdoing = trim($this->input['stopdoing']);
        $doing = trim($this->input['doing']);
        $town = trim($this->input['town']);
        $hotel_id = trim($this->input['hotel_id']);
        $name = trim($this->input['name']);
        $email = trim($this->input['email']);
        $stars = intval($this->input['stars']);

        /* Validation */
        $return = $this->validation([
            'name|ФИО' => [$name, 'required|min:3|max:300'],
            'email|Электронная_почта' => [$email, 'email|min:3|max:300'],
            'town|Город' => [$town, 'required|max:1000']
        ]);

        if($return['messages']){
            $return['success'] = false;
            $this->json($return);
            return;
        };


        /* Validate images */
        $images = [];
        foreach ($this->input as $key => $image){
            if(preg_match('/^image/',$key)){
                if($image!='undefined'){
                    $ext = pathinfo($image->getClientOriginalName())['extension'];
                    if($ext !='jpg' && $ext !='jpeg' && $ext !='png'){
                        $return['messages'][] = 'Не правильный формат изображения';
                    } else {
                        $images[] = $image;
                    }
                }
            }
        }

        if($return['messages']){
            $return['success'] = false;
            $this->json($return);
            return;
        };

        //Log::debug(print_r($this->input,1));

        $review = new Review;
        $review->hotel_id = $hotel_id;
        $review->comment = $comment;
        $review->liked = $liked;
        $review->startdoing = $startdoing;
        $review->stopdoing = $stopdoing;
        $review->doing = $doing;
        $review->name = $name;
        $review->email = $email;
        $review->town = $town;
        $review->stars = $stars;
        $review->save();

        if(count($images))
            foreach ($images as $image){
                $file = new File;
                $file->data = $image;
                $file->is_public = true;
                $file->save();
                $review->images()->add($file);
            }

        $return['messages']  = ['Спасибо за отзыв, он появится после модерации'];
        $return['success'] = true;
        $return['badfields'] = [];
        $this->json($return);

    }
}