<?php namespace Zen\Worker\Console\volga;

use Exception;
use Zen\Worker\Classes\ProcessLog;

/**
 * Клиент структурированных стоянок VolgaWolga.
 *
 * type1 содержит простые проходки, type2 — составные. В type1 для составных
 * круизов присутствует пустой узел, поэтому непустой type2 дополняет/заменяет его.
 */
class VolgaRouteClient
{
    private $timeout;
    private $cacheDir;
    private $type1Url;
    private $type2Url;

    public function __construct(
        $timeout = 180,
        $type1Url = 'http://test.volgawolga.ru/php/xml/2026/index-if-track-type1.php',
        $type2Url = 'http://test.volgawolga.ru/php/xml/2026/index-if-track-type2.php'
    ) {
        $this->timeout = max(180, (int) $timeout);
        $this->type1Url = $type1Url;
        $this->type2Url = $type2Url;
        $this->cacheDir = storage_path('parsers_cache/volga');

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    public function getRoutes(bool $force = false): array
    {
        $type1 = $this->download(
            $this->type1Url,
            $this->cacheDir . '/route_type1.xml',
            $force
        );
        $type2 = $this->download(
            $this->type2Url,
            $this->cacheDir . '/route_type2.xml',
            $force
        );

        return self::mergeXml($type1, $type2);
    }

    public function clearCache(): void
    {
        foreach (['route_type1.xml', 'route_type2.xml'] as $file) {
            $path = $this->cacheDir . '/' . $file;
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    public static function mergeXml(string $type1Xml, string $type2Xml): array
    {
        $routes = self::parseXml($type1Xml, 1);

        foreach (self::parseXml($type2Xml, 2) as $cruiseId => $points) {
            if (!empty($points)) {
                $routes[$cruiseId] = $points;
            }
        }

        return $routes;
    }

    private static function parseXml(string $xmlContent, int $trackingType): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new Exception(
                'Ошибка XML маршрутов Volga: ' .
                ($errors ? trim($errors[0]->message) : 'неизвестная ошибка')
            );
        }

        $routes = [];
        foreach ($xml->xpath('//cruise') ?: [] as $cruise) {
            $cruiseId = (int) $cruise['id'];
            if (!$cruiseId) {
                continue;
            }

            $points = [];
            foreach ($cruise->TrackPoint as $point) {
                $attributes = $point->attributes();
                $name = trim((string) $attributes['Point']);
                if ($name === '') {
                    continue;
                }

                $points[] = [
                    'tracking_type' => $trackingType,
                    'track_id' => (int) $attributes['TrackId'],
                    'point' => $name,
                    'arrival' => trim((string) $attributes['Arrival']),
                    'departure' => trim((string) $attributes['Departure']),
                    'stay_time' => trim((string) $attributes['StayTime']),
                ];
            }

            $routes[$cruiseId] = $points;
        }

        return $routes;
    }

    private function download(string $url, string $cachePath, bool $force): string
    {
        if (!$force && file_exists($cachePath) && filesize($cachePath) > 0) {
            return (string) file_get_contents($cachePath);
        }

        ProcessLog::add("Volga routes: скачивание {$url}");
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'GET',
                'header' => ['User-Agent: Mozilla/5.0 (compatible; VolgaParser/1.0)']
            ]
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; VolgaParser/1.0)');
            $content = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($content === false || $status !== 200) {
                throw new Exception(
                    "Ошибка маршрутов Volga HTTP {$status}: {$error}"
                );
            }
        }

        if (!$content) {
            throw new Exception("Пустой ответ маршрутов Volga: {$url}");
        }

        if (file_put_contents($cachePath, $content) === false) {
            throw new Exception("Не удалось сохранить кеш маршрутов Volga: {$cachePath}");
        }

        return $content;
    }
}
