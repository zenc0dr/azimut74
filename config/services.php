<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => '',
        'secret' => '',
        'endpoint' => 'api.mailgun.net', // api.eu.mailgun.net for EU
    ],

    'mandrill' => [
        'secret' => '',
    ],

    'ses' => [
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => '',
    ],

    'stripe' => [
        'model'  => 'User',
        'secret' => '',
    ],

    /*
    | Rocket.Chat incoming webhook (worker-уведомления).
    | URL и алиас читаются из .env, но доступ через config(), чтобы работало
    | и при php artisan config:cache (env() вне config-файлов тогда даёт null).
    |
    | Дублируем имя переменной из Axis (chub): DEV_BOT_ROCKETCHAT_WEBHOOK_URL —
    | можно задать один и тот же webhook в .env для обоих проектов.
    */
    'rocket_chat' => [
        'support_bot_webhook' => trim((string) (env('ROCKET_CHAT_SUPPORT_BOT_WEBHOOK') ?: env('DEV_BOT_ROCKETCHAT_WEBHOOK_URL', ''))),
        'azimut_general_webhook' => trim((string) env('ROCKET_CHAT_AZIMUT_GENERAL_WEBHOOK', '')),
        'webhook_alias' => env('ROCKET_CHAT_WEBHOOK_ALIAS'),
    ],

    /*
    | Сигнал на AXIS prod: появился новый отзыв, AXIS сам забирает его из этой базы.
    | Пустой URL или токен — запрос не отправляется, сохранение отзыва не зависит от AXIS.
    */
    'axis' => [
        'reviews_notify_url' => trim((string) env(
            'AXIS_REVIEWS_NOTIFY_URL',
            'https://axis-prod.os3.pro/chub.api/ReviewApi:legacyNotify'
        )),
        'reviews_notify_token' => trim((string) env('AXIS_REVIEWS_NOTIFY_TOKEN', '')),
    ],

];
