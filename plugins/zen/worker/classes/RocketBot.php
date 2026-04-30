<?php namespace Zen\Worker\Classes;

/**
 * Уведомления в Rocket.Chat (incoming webhook).
 *
 * Запрос делается через libcurl напрямую (как рабочий curl и как в Axis по смыслу),
 * без October Rain Http — там возможны расхождения с ответом/кодом.
 *
 * TODO: URL сейчас захардкожен; после стабилизации — config / .env.
 */
class RocketBot
{
    /** Тот же incoming webhook, что в Axis `SupportRocketBot::deliverToRocket`. */
    private const SUPPORT_WEBHOOK_URL = 'https://chat-varuna.os3.pro/hooks/autopost-development-cursor-bridge/rcb_dev_ut5b5r8ka7d';

    public static function send(string $message): void
    {
        self::push($message);
    }

    /**
     * @return array{
     *   ok: bool,
     *   http_code?: int,
     *   body_snippet?: string,
     *   skipped?: string,
     *   exception?: string
     * }
     */
    public static function push(string $message): array
    {
        $url = self::SUPPORT_WEBHOOK_URL;

        $payload = ['text' => $message];
        $alias = trim((string) config('services.rocket_chat.webhook_alias', ''));
        if ($alias !== '') {
            $payload['alias'] = $alias;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log('RocketBot: json_encode failed');
            return ['ok' => false, 'skipped' => 'json_encode_failed'];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'exception' => 'PHP cURL extension not available'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($json),
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
        ]);

        $body = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrno !== 0) {
            $msg = "cURL {$curlErrno}: {$curlError}";
            error_log('RocketBot: ' . $msg);
            return ['ok' => false, 'exception' => $msg];
        }

        $bodyStr = is_string($body) ? $body : '';
        $snippet = function_exists('mb_substr') ? mb_substr($bodyStr, 0, 500) : substr($bodyStr, 0, 500);

        if ($code < 200 || $code >= 300) {
            error_log("RocketBot: HTTP {$code}, body: {$snippet}");
            return [
                'ok' => false,
                'http_code' => $code,
                'body_snippet' => $snippet,
            ];
        }

        return [
            'ok' => true,
            'http_code' => $code,
            'body_snippet' => $snippet !== '' ? $snippet : null,
        ];
    }
}
