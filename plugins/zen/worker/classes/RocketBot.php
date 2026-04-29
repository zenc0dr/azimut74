<?php namespace Zen\Worker\Classes;

use Http;

class RocketBot
{
    public static function send(string $message): void
    {
        $url = env('ROCKET_CHAT_SUPPORT_BOT_WEBHOOK');
        if ($url === null || $url === '') {
            return;
        }

        $payload = json_encode(['text' => $message], JSON_UNESCAPED_UNICODE);

        try {
            Http::post($url, function ($http) use ($payload) {
                $http->header('Content-Type', 'application/json; charset=utf-8');
                $http->setOption(CURLOPT_POSTFIELDS, $payload);
            });
        } catch (\Throwable $e) {
            error_log('RocketBot: ' . $e->getMessage());
        }
    }
}
