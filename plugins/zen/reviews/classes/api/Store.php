<?php

namespace Zen\Reviews\Classes\Api;

use Zen\Cli\Classes\Cli;
use Zen\Reviews\Classes\Services\ValidatorHelper;
use Zen\Reviews\Classes\Services\Images\Image;
use Zen\Reviews\Models\Review;
use Zen\Uongate\Classes\Lead;
use Zen\Reviews\Models\Email as EmailModel;
use Http;
use File;

class Store
{
    # Добавление нового отзыва
    # http://azimut.dc/zen/reviews/api/Store:push
    public function push()
    {
//        reviews()->files()->arrayToFile(
//            request()->all()
//        );
        //$form = reviews()->files()->arrayFromFile()['form'];
        $form = request('form');

        # Предварительная обработка
        $validator = new ValidatorHelper();

        $input_data = [
            'name|Имя|min:3|max:255|required' => $form['name'],
            'ship_id|Теплоход|required' => $form['ship_id'],
            'trip_date|Когда_вы_ездили|required' => $form['trip_date'],
            'exp_rest|required' => $form['exp_rest'],
            'reviews_text|Напишите_отзыв|required|min:200|required' => $form['reviews_text']
        ];

        $validator->validate($input_data);

        $alerts = $validator->alerts();

        if ($alerts) {
            return reviews()->toJson([
                'success' => false,
                'alerts' => $alerts
            ]);
        }

        $ship = reviews()
            ->db('mcmraak_rivercrs_motorships')
            ->where('id', $form['ship_id'])
            ->first();

        if ($ship) {
            $form['ship_name'] = $ship->name;
        }

        $photos = $form['photos'];
        unset($form['photos']);

        $review = new Review();
        $review->name = $form['name'];
        $review->data = $form;
        $review->save();

        $this->sendReport($form);
        $this->whSend($form, $review);

        $files_insert = [];
        $sort_order = 0;
        foreach ($photos as $photo) {
            $sort_order++;
            $ext = pathinfo($photo['disk_name'], PATHINFO_EXTENSION);
            if ($ext === 'jpg') {
                $ext = 'jpeg';
            }
            $files_insert[] = [
                'disk_name' => $photo['disk_name'],
                'file_name' => $photo['file_name'],
                'file_size' => filesize(Image::getPublicPath($photo['disk_name'])),
                'content_type' => "image/$ext",
                'field' => 'photos',
                'attachment_id' => $review->id,
                'attachment_type' => 'Zen\Reviews\Models\Review',
                'is_public' => 1,
                'sort_order' => $sort_order,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        reviews()->db('system_files')->insert($files_insert);
        return reviews()->toJson([
            'success' => true,
            'review_id' => $review->id,
            'alerts' => [
                [
                    'text' => "Спасибо за Ваш отзыв!",
                    'type' => 'success'
                ]
            ],
        ]);
    }

    # Send to webhook https://8ber.kaiten.ru/28898374
    private function whSend(array $form, Review $review)
    {
        $lead_id = $form['lead_id'] ?? null;
        if (!$lead_id) {
            return;
        }

        $data = [
            'lead_id' => $lead_id,
            'url_review' => env('APP_URL') . '/console/zen/reviews/reviews/update/' . $review->id
        ];

        $url = 'https://tglk.ru/in/5nJ6Rix6nUzXMeq5';

        $response = Http::post($url, function ($http) use ($data) {
            $http->data($data);
        });

        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        \Log::debug("Webhook send: url:$url data:$data response:$response->body");
    }

    private function sendReport(array $data): void
    {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $cli = new Cli;
        $cli->nohup = false;
        $cli->artisanExec("artisan reviews:report --json_data='$data'");
    }

    public function sendPhone()
    {
        $review_id = request('review_id');
        $phone = request('phone');
        $email = request('email');
        $phone = preg_replace('/\D+/', '', $phone);
        $review = Review::find($review_id);
        $review->phone = $phone;

        if ($email) {
            $review->email = $email;
            $this->sendEmail($email, $review);
        }

        $review->save();

        Lead::push([
            'source' => 'reviews',
            'name' => $review->data['name'],
            'phone' => $phone,
            'email' => $email,
            'note' => 'Добавлен отзыв https://xn----7sbveuzmbgd.xn--p1ai/console/zen/reviews/reviews/update/'
                . $review->id,
            'Amo.Integration' => [
                'phone' => $phone,
                'email' => $email,
                'item_url' => 'https://xn----7sbveuzmbgd.xn--p1ai/console/zen/reviews/reviews/update/' . $review->id
            ]
        ]);

        return reviews()->toJson([
            'success' => true,
        ]);
    }

    # http://azimut.dc/zen/reviews/api/Store:sendEmail?debug=test@bk.ru
    public function sendEmail(string $email = null, Review $review = null)
    {
        $email = $email ?? request('email') ?? request('debug');

        if (!$review) {
            $review = Review::find(request('review_id'));
            if (!$review) {
                return reviews()->toJson([
                    'success' => false,
                ]);
            }
            $review->email = $email;
            $review->save();
        }

        $subject = 'Ваш купон';

        #Полный путь указывать
        $data = [
            'coupon_img_path' => 'https://xn----7sbveuzmbgd.xn--p1ai/plugins/zen/reviews/assets/images/coupon-az.png',
            'url_red_btn' => 'https://xn----7sbveuzmbgd.xn--p1ai/#callme',
            'url_blue_btn' => 'https://xn----7sbveuzmbgd.xn--p1ai/russia-river-cruises'
        ];

        \Mail::send('zen.reviews::mail.coupon', $data, function ($message) use ($subject, $email) {
            $message->subject($subject);
            $message->to($email);
        });

        return reviews()->toJson([
            'success' => true,
        ]);
    }

    # Добавление нового изображения
    # http://azimut.dc/zen/reviews/api/Store:addImage
    public function addImage()
    {
        $image = reviews()->image(request('image'))->store(); // input: image => [file_name, base64]
        return reviews()->toJson([
            'success' => true,
            'image' => [
                'url' => $image->getUrl(),
                'disk_name' => $image->getDiskName(),
                'file_name' => $image->getFileName()
            ]
        ]);
    }

    # http://azimut.dc/zen/reviews/api/Store:sendInvitationEmail
    public function sendInvitationEmail()
    {
        $email = request('email');
        //$path = storage_path('sending_emails.txt');
        $password = request('password');
        $auth = false;
        if ($password === 'jdy36Fbdj48gd') {
            $auth = true;
        }
        $emails = [];
        $alert = null;
//        if (file_exists($path)) {
//            $emails = explode(PHP_EOL, file_get_contents($path));
//        }

        //$mails_count = count($emails);
        $mails_count = EmailModel::count();

        if ($email) {
            $email_model = EmailModel::where('email', $email)->first();
            if ($email_model) {
                $alert = "Почта $email уже была отправлена";
            } else {
                try {
                    \Mail::send('review-invite', [
                        'email' => $email
                    ], function ($message) use ($email) {
                        $message->to($email);
                    });
                    $alert = "Почта $email ушла";
                    EmailModel::create([
                        'email' => $email
                    ]);
                    //file_put_contents(storage_path('sending_emails.txt'), $email . PHP_EOL, FILE_APPEND);
                } catch (\Exception|\Throwable $ex) {
                    $error_message = $ex->getFile() . ':' . $ex->getLine() . ' - ' . $ex->getMessage();
                    $alert = "Ошибка $error_message";
                }
            }
        }
        return reviews()->toJson([
            'success' => true,
            'count' => $mails_count,
            'alert' => $alert,
            'auth' => $auth
        ]);
    }
}
