<?php

namespace Zen\Master\Classes\Services;

use Zen\Master\Traits\SingletonTrait;

class OutboundHttp
{
    use SingletonTrait;

    const EVENT_FAIL = 'AMO.send.fail';
    const EVENT_RETRY = 'AMO.send.retry';
    const EVENT_RETRY_OK = 'AMO.send.retry.ok';
    const BODY_LIMIT = 8192;
    const ATTEMPTS = 5;
    const RETRY_PAUSE_SEC = 20;

    /**
     * POST form/array payload. Успех: HTTP 2xx и непустое тело — тогда ничего не пишем
     * (кроме случая, когда успех после ретрая — тогда AMO.send.retry.ok).
     *
     * При недоступности удалённого сервера: до ATTEMPTS попыток, пауза RETRY_PAUSE_SEC между ними.
     * Вызывается из uongate:lead_push (фон, `&`), бронь клиента не ждёт.
     *
     * @return bool true = доставлено, false = все попытки исчерпаны или ошибка без ретрая
     */
    public function post(string $url, array $payload, int $timeout = 15): bool
    {
        $lastRecord = null;

        for ($attempt = 1; $attempt <= self::ATTEMPTS; $attempt++) {
            $record = $this->attemptPost($url, $payload, $timeout, $attempt);
            if ($record === null) {
                if ($attempt > 1) {
                    master()->log(self::EVENT_RETRY_OK, [
                        'url' => $url,
                        'attempt' => $attempt,
                        'attempts' => self::ATTEMPTS,
                        'source' => $payload['source'] ?? null,
                    ]);
                }
                return true;
            }

            $lastRecord = $record;
            $retryable = $this->isRetryable($record);
            $hasMore = $attempt < self::ATTEMPTS;

            if ($retryable && $hasMore) {
                $record['next_in_sec'] = self::RETRY_PAUSE_SEC;
                master()->log(self::EVENT_RETRY, $record);
                $this->pauseBeforeRetry($timeout);
                continue;
            }

            $record['attempts'] = $attempt;
            master()->log(self::EVENT_FAIL, $record);
            return false;
        }

        if ($lastRecord !== null) {
            $lastRecord['attempts'] = self::ATTEMPTS;
            master()->log(self::EVENT_FAIL, $lastRecord);
        }

        return false;
    }

    public function logException(array $context, \Exception $e): void
    {
        master()->log(self::EVENT_FAIL, array_merge([
            'kind' => $this->kindFromException($e),
            'url' => $context['url'] ?? null,
            'http_code' => null,
            'body' => null,
            'headers' => null,
            'duration_ms' => null,
            'exception' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
            'source' => $context['source'] ?? null,
            'payload' => $context['payload'] ?? null,
            'attempt' => 1,
            'attempts' => 1,
        ], $context));
    }

    /**
     * @return array|null null = успех
     */
    private function attemptPost(string $url, array $payload, int $timeout, int $attempt): ?array
    {
        $started = microtime(true);
        $record = [
            'kind' => null,
            'url' => $url,
            'http_code' => null,
            'body' => null,
            'headers' => null,
            'duration_ms' => null,
            'exception' => null,
            'source' => $payload['source'] ?? null,
            'payload' => $payload,
            'attempt' => $attempt,
            'attempts' => self::ATTEMPTS,
        ];

        try {
            $response = \Http::post($url, function ($http) use ($payload, $timeout) {
                if (method_exists($http, 'setOption')) {
                    $http->setOption(CURLOPT_TIMEOUT, $timeout);
                    $http->setOption(CURLOPT_CONNECTTIMEOUT, min(10, $timeout));
                }
                $http->data($payload);
            });

            $record['duration_ms'] = (int) round((microtime(true) - $started) * 1000);
            $code = isset($response->code) ? (int) $response->code : 0;
            $body = $this->stringifyBody($response->body ?? null);
            $record['http_code'] = $code;
            $record['body'] = $this->clip($body);
            $record['headers'] = $this->normalizeHeaders($response->headers ?? null);

            if ($code === 0) {
                $record['kind'] = 'connection';
            } elseif ($code < 200 || $code >= 300) {
                $record['kind'] = 'http_error';
            } elseif ($body === '') {
                $record['kind'] = 'empty_body';
            } else {
                return null;
            }
        } catch (\Exception $e) {
            $record['duration_ms'] = (int) round((microtime(true) - $started) * 1000);
            $record['exception'] = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            $record['kind'] = $this->kindFromException($e);
        }

        return $record;
    }

    private function isRetryable(array $record): bool
    {
        $kind = $record['kind'] ?? '';
        $code = (int) ($record['http_code'] ?? 0);

        if ($kind === 'timeout' || $kind === 'connection' || $kind === 'empty_body') {
            return true;
        }

        if ($kind === 'http_error') {
            return $code === 0 || $code === 408 || $code === 429 || $code >= 500;
        }

        return false;
    }

    private function pauseBeforeRetry(int $timeout): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(self::RETRY_PAUSE_SEC + $timeout + 30);
        }
        sleep(self::RETRY_PAUSE_SEC);
    }

    private function kindFromException(\Exception $e): string
    {
        $msg = strtolower($e->getMessage());
        if (
            strpos($msg, 'timed out') !== false
            || strpos($msg, 'timeout') !== false
            || strpos($msg, 'operation timed out') !== false
        ) {
            return 'timeout';
        }
        if (
            strpos($msg, 'could not resolve') !== false
            || strpos($msg, 'connection refused') !== false
            || strpos($msg, 'failed to connect') !== false
            || strpos($msg, 'network is unreachable') !== false
        ) {
            return 'connection';
        }
        return 'exception';
    }

    private function stringifyBody($body): string
    {
        if ($body === null || $body === false) {
            return '';
        }
        if (is_array($body) || is_object($body)) {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $json === false ? '' : $json;
        }
        return trim((string) $body);
    }

    private function clip(string $body): string
    {
        if (strlen($body) <= self::BODY_LIMIT) {
            return $body;
        }
        return substr($body, 0, self::BODY_LIMIT) . '…';
    }

    private function normalizeHeaders($headers)
    {
        if ($headers === null || $headers === []) {
            return null;
        }
        if (is_string($headers)) {
            return $this->clip($headers);
        }
        if (is_array($headers)) {
            $json = json_encode($headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $json === false ? null : $this->clip($json);
        }
        return null;
    }
}
