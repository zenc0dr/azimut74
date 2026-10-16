<?php namespace Zen\Reviews\Classes;

/**
 * Короткий сигнал на AXIS: в базе легаси появился новый отзыв.
 * AXIS забирает запись сам. Сбой запроса не мешает сохранению отзыва.
 */
class AxisReviewNotifier
{
    public static function notifyNewReview(int $reviewId): void
    {
        if ($reviewId <= 0) {
            return;
        }

        $url = trim((string) config('services.axis.reviews_notify_url', ''));
        $token = trim((string) config('services.axis.reviews_notify_token', ''));
        if ($url === '' || $token === '') {
            return;
        }

        $json = json_encode(['review_id' => $reviewId], JSON_UNESCAPED_UNICODE);
        if ($json === false || !function_exists('curl_init')) {
            return;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
                'Content-Length: ' . strlen($json),
            ],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $body = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrno !== 0) {
            error_log('Reviews AxisReviewNotifier: cURL ' . $curlErrno . ': ' . $curlError);

            return;
        }

        if ($code < 200 || $code >= 300) {
            $snippet = is_string($body) ? substr($body, 0, 300) : '';
            error_log('Reviews AxisReviewNotifier: HTTP ' . $code . ' review_id=' . $reviewId . ' body=' . $snippet);
        }
    }
}
