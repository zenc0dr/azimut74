<?php namespace Mcmraak\Rivercrs\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Mcmraak\Rivercrs\Models\Review;
use Validator;
use System\Models\File;
use Mcmraak\Rivercrs\Models\Settings;
use Mail;
use Log;

class Reviews extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'mcmraak.rivercrs.motorships' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-reviews');
    }

    public static function sendReview($data)
    {

        //print_r($data); die();
        /* $data = Array
            (
                [motorship_id] => 13
                [comment] => Добавить комментарий
                [startdoing] => Что надо начать делать
                [stopdoing] => Что надо перестать делать
                [doing] => Что надо продолжать делать
                [name] => Ваше имя
                [email] => Электронная почта
                [town] => Ваш город
                [recommended] => 1
            )*/
        //print_r($data);

        if(!intval($data['motorship_id']))
        {
            return "<div class='error'>Ошибка базы данных!</div>";
        }


        $data['recommended'] = intval($data['recommended']);

        $return = '';

        $images = [];
        foreach ($data as $key => $image){
            if(preg_match('/^image/',$key)){
                if($image!='undefined'){
                    $ext = pathinfo($image->getClientOriginalName())['extension'];
                    if($ext !='jpg' && $ext !='jpeg' && $ext !='png'){
                        $return .= "<div class='error'>Не правильный формат изображения</div>";
                    } else {
                        $images[] = $image;
                    }
                }
            }
        }


        $validator = Validator::make(
            [
                'Имя' => $data['name'],
                'Электронная_почта' => $data['email'],
                'Добавить_комментарий' => $data['comment'],
                'Что особенно понравилось' => $data['liked'],
                'Что не понравилось' => $data['notliked'],
                'Что_надо_начать_делать' => $data['startdoing'],
                'Что_надо_перестать_делать' => $data['stopdoing'],
                'Что_надо_продолжать_делать' => $data['doing'],
                'Ваш_город' => $data['town'],
            ],
            [
                'Имя' => 'required|min:2|max:100',
                'Электронная_почта' => 'email|min:6|max:300',
                'Добавить_комментарий' => 'max:2000',
                'Что_надо_начать_делать' => 'max:2000',
                'Что_надо_перестать_делать' => 'max:2000',
                'Что_надо_продолжать_делать' => 'max:2000',
                'Ваш_город' => 'required|max:100',
            ]
        );

        if ($validator->fails())
        {
            $alerts = $validator->messages()->all();
            foreach ($alerts as $alert)
            {
                $return .= "<div class='error'>$alert</div>";
            }
        }
        else
        {

            $review = new Review;
            $review->motorship_id = $data['motorship_id'];
            $review->email = $data['email'];
            $review->recommended = $data['recommended'];
            $review->name = $data['name'];
            $review->comment = $data['comment'];
            $review->liked = $data['liked'];
            $review->notliked = $data['notliked'];
            $review->startdoing = $data['startdoing'];
            $review->stopdoing = $data['stopdoing'];
            $review->doing = $data['doing'];
            $review->town = $data['town'];
            $review->save();

            if(count($images))
            foreach ($images as $image){
                $file = new File;
                $file->data = $image;
                $file->is_public = true;
                $file->save();
                $review->files()->add($file);
            }

            #Send Email
            $settings = Settings::find(1);

            $emails = $settings->reviewsemails;

            $emailsToSend = [];
            foreach ($emails as $email)
            {
                $emailsToSend[] = $email['remail'];
            }

            $from = 'RiverCRS';
            $to = 'Администраторам';
            //$subj = 'Отзыв на ['.$_SERVER['HTTP_HOST'].']';
			$subj = 'Отзыв на азимут-тур.рф';

			//$mail = 'sarnews64@yandex.ru';

            Mail::send([
              'text' => "",
              'html' => \View::make('mcmraak.rivercrs::review_report',['record' => $review]),
              'raw' => true
            ], [null], function ($message) use ($from, $emailsToSend, $to, $subj){
               //$message->from('noreply@'.$_SERVER['HTTP_HOST'], $from);
        	   $message->to($emailsToSend, $to);
               $message->subject($subj);
            });

			//Mail::send('empty', ['html' => \View::make('mcmraak.rivercrs::review_report',['record' => $review])], function($msg) use ($mail, $to) {
	        //    $msg->to($mail, $to);
	        //});
			$mail= 'sarnews64@yandex.ru';
			Mail::send('empty', ['html' => \View::make('mcmraak.rivercrs::review_report',['record' => $review])], function($msg) use ($mail, $to) {
	            $msg->to($mail, $to);
	        });

        }

        if($return == '') $return = "<div class='message'>Спасибо за Ваш отзыв!<script>closeReview();</script></div>";
        return $return;
    }
}