<?php namespace Zen\Uongate\Classes;

/**
 * Алерт в Rocket.Chat #Azimut_general, если заявка не ушла в AMO после всех попыток.
 * Incoming webhook Varuna: config services.rocket_chat.azimut_general_webhook (.env, не git).
 */
class AmoFailAlert
{
    const EVENT_ROCKET_FAIL = 'AMO.rocket.fail';

    public static function send(array $amo): void
    {
        try {
            $url = trim((string) config('services.rocket_chat.azimut_general_webhook', ''));
            if ($url === '') {
                master()->log(self::EVENT_ROCKET_FAIL, [
                    'skipped' => 'webhook_not_configured',
                    'source' => $amo['source'] ?? null,
                ]);
                return;
            }

            $result = self::postJson($url, ['text' => self::buildMessage($amo)]);
            if (empty($result['ok'])) {
                master()->log(self::EVENT_ROCKET_FAIL, array_merge($result, [
                    'source' => $amo['source'] ?? null,
                    'name' => $amo['name'] ?? null,
                    'phone' => $amo['phone'] ?? null,
                ]));
            }
        } catch (\Exception $e) {
            master()->log(self::EVENT_ROCKET_FAIL, [
                'exception' => $e->getMessage(),
                'source' => $amo['source'] ?? null,
            ]);
        }
    }

    public static function buildMessage(array $amo): string
    {
        $lines = [
            ':warning: *Внимание, новая заявка*',
            'В АМО заявки нет, берите данные отсюда:',
            '',
        ];

        $fields = [
            'ФИО' => self::str($amo, 'name'),
            'Телефон' => self::str($amo, 'phone'),
            'Email' => self::str($amo, 'email'),
            'Теплоход' => self::str($amo, 'ship_name'),
            'Даты' => self::dates($amo),
            'Маршрут' => self::str($amo, 'waybill'),
            'Город' => self::str($amo, 'town'),
            'Комментарий' => self::str($amo, 'desc'),
            'Страница' => self::str($amo, 'page_url'),
            'Источник' => self::str($amo, 'source'),
        ];

        foreach ($fields as $label => $value) {
            if ($value === '') {
                continue;
            }
            $lines[] = '*' . $label . ':* ' . $value;
        }

        $orderBlock = self::formatOrder($amo['order'] ?? null);
        if ($orderBlock !== '') {
            $lines[] = '';
            $lines[] = '*Бронь:*';
            $lines[] = $orderBlock;
        }

        $lines[] = '';
        $lines[] = ':warning: Кто берёт в работу, пишите ответом: `+` или «Взял в работу»';

        return implode("\n", $lines);
    }

    private static function dates(array $amo): string
    {
        $of = self::str($amo, 'date_of');
        $to = self::str($amo, 'date_to');
        if ($of === '' && $to === '') {
            return '';
        }
        $range = trim($of . ' — ' . $to, ' —');
        $tOf = self::str($amo, 'time_of');
        $tTo = self::str($amo, 'time_to');
        if ($tOf !== '' || $tTo !== '') {
            $range .= ' (' . trim($tOf . '–' . $tTo, '–') . ')';
        }
        return $range;
    }

    private static function formatOrder($order): string
    {
        if ($order === null || $order === '' || $order === []) {
            return '';
        }
        if (is_string($order)) {
            return $order;
        }
        if (!is_array($order)) {
            return '';
        }

        $out = [];
        if (isset($order['peoples']) && $order['peoples'] !== '' && $order['peoples'] !== null) {
            $out[] = 'Человек: ' . $order['peoples'];
        }
        if (!empty($order['cabins']) && is_array($order['cabins'])) {
            foreach ($order['cabins'] as $cabin) {
                if (!is_array($cabin)) {
                    continue;
                }
                $num = $cabin['num'] ?? $cabin['cabin_number'] ?? '';
                $name = $cabin['cabin_name'] ?? '';
                $deck = $cabin['deck_name'] ?? '';
                $out[] = trim('Каюта ' . $num . ' ' . $name . ($deck !== '' ? ', палуба ' . $deck : ''));
                if (!empty($cabin['prices']) && is_array($cabin['prices'])) {
                    foreach ($cabin['prices'] as $price) {
                        if (!is_array($price)) {
                            continue;
                        }
                        $places = $price['price_places'] ?? '';
                        $value = $price['price_value'] ?? '';
                        $out[] = '  мест: ' . $places . ', цена: ' . $value;
                    }
                }
            }
        }
        if ($out === []) {
            $json = json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $json === false ? '' : $json;
        }
        return implode("\n", $out);
    }

    private static function str(array $amo, string $key): string
    {
        if (!isset($amo[$key]) || $amo[$key] === null || $amo[$key] === '') {
            return '';
        }
        if (is_array($amo[$key]) || is_object($amo[$key])) {
            $json = json_encode($amo[$key], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $json === false ? '' : $json;
        }
        return trim((string) $amo[$key]);
    }

    /**
     * @return array{ok: bool, http_code?: int, body_snippet?: string, skipped?: string, exception?: string}
     */
    private static function postJson(string $url, array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
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
            return ['ok' => false, 'exception' => "cURL {$curlErrno}: {$curlError}"];
        }

        $bodyStr = is_string($body) ? $body : '';
        $snippet = function_exists('mb_substr') ? mb_substr($bodyStr, 0, 300) : substr($bodyStr, 0, 300);

        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'http_code' => $code, 'body_snippet' => $snippet];
        }

        return ['ok' => true, 'http_code' => $code];
    }
}
