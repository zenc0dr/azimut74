<?php

namespace Zen\Quiz\Classes\Api;

use Zen\Quiz\Models\Application;
use Zen\Quiz\Classes\Services\ValidatorHelper;
use Zen\Uongate\Classes\Lead;
use Mail;
use Carbon\Carbon;

class Store
{
    # Добавление новой заявки
    # http://azimut.dc/zen/quiz/api/Store:push
    public function push()
    {
        $form = request('form');

        # Предварительная обработка
        $validator = new ValidatorHelper();

        $input_data = [
            'email|Email|email' => $form['email'],
            'phone|Телефон|min:11|required' => $form['phone']
        ];

        $validator->validate($input_data);

        $alerts = $validator->alerts();

        if ($alerts) {
            return [
                'success' => false,
                'alerts' => $alerts
            ];
        }

        $item = new Application();
        $item->city_id = $form['city_id'];
        $item->date_from = $form['dates']['from'];
        $item->date_to = $form['dates']['to'];
        $item->day_to = $form['days']['to'];
        $item->day_from = $form['days']['from'];
        $item->count_adult = $form['people']['adult'];
        $item->count_children = $form['people']['children'];
        $item->phone = $form['phone'];
        $item->email = $form['email'];
        $item->save();

        $note = "Название формы: Подберите мне круиз;\n" .
            "Телефон: {$form['phone']};\n" .
            "email: {$form['email']};\n" .
            "Город: {$item->city->name}\n" .
            "Дата С: {$form['dates']['from']}\n" .
            "Дата ПО: {$form['dates']['to']}\n" .
            "Дней С: {$form['days']['from']}\n" .
            "Дней ПО: {$form['days']['to']}\n" .
            "Количество взрослых: {$form['people']['adult']}\n" .
            "Количество детей: {$form['people']['children']}\n" .
            "Подробно: https://xn----7sbveuzmbgd.xn--p1ai/console/zen/quiz/applications/update/$item->id;";

        Lead::push([
            'source' => 'quiz',
            'name' => 'Lead',
            'phone' => $form['phone'],
            'email' => $form['email'],
            'note' => $note,
            'Amo.Integration' => [
                'name' => null,
                'phone' => $form['phone'],
                'email' => $form['email'],
                'page_url' => null,
                'city' => $item->city->name,
                'adults' => $form['people']['adult'],
                'childrens' => $form['people']['children'],
                'date_of' => Carbon::parse($form['dates']['from'])->format('d.m.Y'),
                'date_to' => Carbon::parse($form['dates']['to'])->format('d.m.Y')
            ]
        ]);

        $raw = [
            'html' => "Телефон: {$form['phone']}, email: {$form['email']}, Параметры тура: $note",
            'text' => "Телефон: {$form['phone']}, email: {$form['email']}, Параметры тура: $note",
        ];

        $subject = "Заявка с сайта (Подберите мне круиз) №$item->id";
        $to = 'azimut-kruiz@yandex.ru';

        Mail::raw($raw, function ($message) use ($subject, $to) {
            $message->subject($subject);
            $message->to($to);
        });

        return [
            'success' => true
        ];
    }
}
