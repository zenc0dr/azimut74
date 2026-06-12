<?php namespace Zen\Reviews\Classes;

/**
 * Уведомления о плохих отзывах в Rocket.Chat (канал Azimut_general).
 */
class RocketNotifier
{
    /**
     * @return array{ok: bool, http_code?: int, body_snippet?: string, skipped?: string, exception?: string}
     */
    public static function notifyBadReview(string $message): array
    {
        $url = trim((string) config('services.rocket_chat.azimut_general_webhook', ''));
        if ($url === '') {
            return ['ok' => false, 'skipped' => 'webhook_not_configured'];
        }

        return self::postJson($url, ['text' => $message]);
    }

    public static function buildBadReviewMessage(\Zen\Reviews\Models\Review $review): string
    {
        $form = \Mcmraak\Rivercrs\Classes\ReviewsWidget::extractForm($review);
        $text = trim((string) ($form['reviews_text'] ?? ''));
        $name = trim((string) ($form['name'] ?? $review->name ?? ''));
        $ship = trim((string) ($form['ship_name'] ?? ''));
        $cruise = $review->rating_vacation;
        $azimut = $review->rating_azimut;

        $adminUrl = self::adminReviewUrl((int) $review->id);

        $lines = [
            '🔴 **Внимание, плохой отзыв!**',
            '',
        ];

        if ($name !== '') {
            $lines[] = '**Имя:** ' . $name;
        }
        if ($ship !== '') {
            $lines[] = '**Теплоход:** ' . $ship;
        }
        $lines[] = '**Оценка отдыха:** ' . $cruise;
        $lines[] = '**Оценка Азимут:** ' . $azimut;
        $lines[] = '';
        if ($text !== '') {
            $lines[] = $text;
            $lines[] = '';
        }
        $lines[] = '[Открыть в админке](' . $adminUrl . ')';

        return implode("\n", $lines);
    }

    public static function adminReviewUrl(int $reviewId): string
    {
        $base = rtrim((string) config('app.url', env('APP_URL', '')), '/');

        return $base . '/console/zen/reviews/reviews/update/' . $reviewId;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ok: bool, http_code?: int, body_snippet?: string, skipped?: string, exception?: string}
     */
    private static function postJson(string $url, array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log('Reviews RocketNotifier: json_encode failed');

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
            error_log('Reviews RocketNotifier: ' . $msg);

            return ['ok' => false, 'exception' => $msg];
        }

        $bodyStr = is_string($body) ? $body : '';
        $snippet = function_exists('mb_substr') ? mb_substr($bodyStr, 0, 500) : substr($bodyStr, 0, 500);

        if ($code < 200 || $code >= 300) {
            error_log("Reviews RocketNotifier: HTTP {$code}, body: {$snippet}");

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
