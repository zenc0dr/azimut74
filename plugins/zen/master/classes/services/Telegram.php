<?php

namespace Zen\Master\Classes\Services;

use Zen\Master\Traits\SingletonTrait;
use October\Rain\Support\Facades\Http;

class Telegram
{
    use SingletonTrait;

    public function sendMessage(string $text, string $parse_mode = 'HTML'): void
    {
        $bot_token = '8099503137:AAGUkaYOwBmZtvltrk2WdWuf3k1rVMrmRlk';
        $chat_id = '-1002423009250'; // -1002423009250
        $api_url = 'https://api.telegram.org';
        $encodedText = urlencode($text);

        $query = "$api_url/bot$bot_token/sendMessage?chat_id=$chat_id&text=$encodedText&parse_mode=$parse_mode";
        $response = Http::get($query);
    }
}
