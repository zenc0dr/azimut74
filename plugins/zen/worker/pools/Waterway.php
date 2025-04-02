<?php namespace Zen\Worker\Pools;

/*
 * ВодоходЪ - Документация: https://api-crs.vodohod.com/docs/agency/#tag/SECURITY
 *
 *
 *
 */


use Carbon\Carbon;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Exception;
use Zen\Cabox\Classes\Cabox;
use Zen\Worker\Classes\ProcessLog;

class Waterway extends RiverCrs
{
    private
        $api_url = 'https://api-crs.vodohod.com',
        $api_token = 'JYMucmvXoUwDruvgo',
        $api_login = 'azimut-trk+vodohodapi@yandex.ru',
        $query_attempts = 3;


    public
        $accessToken;

    function auth()
    {
        $data = [
            'login' => $this->api_login,
            'password' => $this->api_token
        ];

        $response = $this->httpQuery([
            'method' => 'security.authorise',
            'data' => $data
        ]);

        $this->accessToken = $response->body['result']['accessToken']['token'];
    }

    function httpQuery($opts)
    {

        // $method, $data = null, $timeout = null

        $default = [
            'method' => null,
            'data' => null,
            'timeout' => null,
        ];

        $opts = (object)array_merge($default, $opts);


        $method = str_replace('.', '/', $opts->method);
        $url = "{$this->api_url}/$method";

        ProcessLog::add("Запрос: $url");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if ($opts->timeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $opts->timeout);
        }

        if ($opts->data) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            $post = json_encode($opts->data, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . mb_strlen($post)
            ]);
        }

        if ($this->accessToken) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$this->accessToken}"
            ]);
        }

        $response = curl_exec($ch);
        $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        if ($code !== 200) {
            file_put_contents(storage_path('worker_last_response.txt'), $response);
        }

        curl_close($ch);

        if ($response) {
            $response = json_decode($response, true);
        }


        return (object)[
            'code' => $code,
            'body' => $response
        ];

//        $response = $this->riverQueryPost($url, 'json', $data, true);
//        return $response;
    }

    function wwQuery($method, $data = null, $cache_key = null)
    {
        if (!$cache_key) {
            $cache_key = $method . '::' . md5(json_encode($data));
        }

        $cache = new Cabox('worker');
        $response_body = $cache->get($cache_key);
        if ($response_body) return $response_body;

        # Авторизация в случае отсутствия ключа
        if (!$this->accessToken) $this->auth();

        # Указание метода
        $opts = ['method' => $method];

        # инъекция данных, если они есть
        if ($data) $opts['data'] = $data;

        $response = $this->httpQuery($opts);

        # Не прошла аутентификация
        if ($response->code == 403 || $response->code != 200 || intval(@$response->body['code']) != 200) {
            if ($response->code == 403) $this->accessToken = null; // Сбрасываем accessToken
            $this->query_attempts--;
            if ($this->query_attempts < 0) {
                throw new Exception('error ww1 '.$method);
            }

            if ($response->code === 500) {
                //dd($response->body);
                ProcessLog::add("Критическая ошибка 500, метод=$method");
                throw new Exception('error ww1 '.$method);
            }

            if ($response->code === 429) {
                ProcessLog::add("Ошибка $response->code: $method (Пауза 5 сек)");
                sleep(5);
            }

            ProcessLog::add("[Error code $response->code] Повтор запроса $method");

            return $this->wwQuery($method, $data); // Повторяем запрос
        }

        $cache->put($cache_key, $response->body);

        return $response->body;
    }

    function wwGraph($routes)
    {
        if (!$routes) return;

        $days_of_week = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
        $return = [];
        $return[] = '<table><tbody>';
        $return[] = "<tr><td>День</td><td>Стоянка</td><td>Описание</td></tr>";
        $start_day = Carbon::parse($routes[0]['in']);
        foreach ($routes as $route) {
            $name = $route['name'];
            $annotation = $route['annotation'];
            $time_start = Carbon::parse($route['in']);
            $time_stop = Carbon::parse($route['out']);
            $time = $this->wwGraphTimeFormat($time_start, $time_stop);
            $day = $start_day->diffInDays($time_start) + 1;
            $day_of_week = $days_of_week[$time_start->dayOfWeek];
            $ex_date = $time_start->format('d.m.Y');
            $return[] = "<tr><td>$day <br>$ex_date<br>$time ($day_of_week)</td><td>$name</td><td>$annotation</td></tr>";
        }
        $return[] = '</tbody></table>';
        $return = join("\n", $return);
        return $return;
    }

    function wwGraphTimeFormat(Carbon $time_start, Carbon $time_stop)
    {
        if ($time_start->format('H:i:s') == '00:00:00') {
            return '<span class="ww_time">Отправление в ' . $time_stop->format('H:i') . '</span>';
        }
        if ($time_stop->format('H:i:s') == '00:00:00') {
            return '<span class="ww_time">Прибытие в ' . $time_start->format('H:i') . '</span>';
        }

        return '<span class="ww_time">' .
            $time_start->format('H:i') .
            ' - ' .
            $time_stop->format('H:i') .
            '</span>';
    }

    function wwRoutesHandler($routes)
    {
        $return = [];
        foreach ($routes as $route) {
            $return[] = [
                'town' => $this->getTownId($route['name']),
                'excursion' => $route['annotation'] ?? '',
                'bold' => 0
            ];
        }
        return $return;
    }
}
