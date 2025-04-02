<?php

namespace Zen\Reviews\Classes\Api;

use Zen\Reviews\Models\Review;
use Http;

class DebugReviews
{
    # http://azimut.dc/zen/reviews/api/DebugReviews:playground
    public function playground()
    {
        $data = [
            'test' => 'Это тестовое сообщение'
        ];

        $response = Http::post('https://tglk.ru/in/5nJ6Rix6nUzXMeq5', function ($http) use ($data) {
            $http->data($data);
        });

        dd($response->body);
    }

     # http://azimut.dc/zen/reviews/api/DebugReviews:sendMail
    public function sendMail() {
        $email = 'darkrogua@inbox.ru';

        \Mail::send('review-invite', [], function ($message) use ($email) {
            $message->to($email);
        });
    }
}
