<?php namespace Zen\Http\Classes;

use Log;

class Http
{
    # Параметры
    public
        $url, #------------------------ (string) Ссылка запроса
        $query_attemps = 5, #---------- (int) Количество допустимых неудачных запросов
        $time_dalay = 0, #------------- (int) Пауза между повторами запроса
        $die_on_failure = true, #------ (bool) При невыполнении запроса остановить скрипт (die)
        $timeout = 10, #--------------- (int) Максимальное время на запрос
        $post = null, #---------------- (array|null) Данные POST
        $post_format = null, #--------- ('json'|null) Формат посылаемых POST-данных
        $response_format = null, #----- ('json'|'xml'|null) Формат ответа для преобразования в массив
        $user_agent = null, #---------- (string|null) Указать явно USER_AGENT
        $basic_auth = null, #---------- (string ex:"$login:$pass") Базовая аутентификация
        $cli_log = false,  #----------- (bool) Выводить сообщения в консоль
        $follow = false; #------------- (bool) Сделовать до последней ссылки при 301 редиректе

    protected
        $code, #----------------------- (int) Код ответа сервера
        $error, #---------------------- (string) Ошибка
        $response = null, #------------ (array|string) Результат выполнения запроса (необработанный)
        $output = null; #-------------- (array|string) Обработанный результат

    protected
        $query_attemps_now = null;

    function __construct($opts=null)
    {
        if($opts) {
            foreach ($opts as $key => $opt)  {
                if(property_exists($this, $key)) $this->{$key} = $opt;
            }
        }
    }

    function get()
    {
        return (object) [
            'code' => $this->code,
            'error' => $this->error,
            'response' => $this->response,
            'output' => $this->output,
        ];
    }

    function getCode()
    {
        return $this->code;
    }

    function getError()
    {
        return $this->error;
    }

    function getResponse()
    {
        return $this->response;
    }

    function getOutput()
    {
        return $this->output;
    }

    function query($url=null)
    {
        # Очистка перед запросом
        $this->query_attemps_now = $this->query_attemps;
        $this->error = null;
        $this->code = null;
        $this->output = null;
        $this->response = null;

        if($url) $this->url = $url;

        $this->execCurlQuery();
        $this->responseHandler();

        return $this->output;
    }

    # Curl запрос
    function execCurlQuery()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        # USER_AGENT
        if($this->user_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        }

        # Базовая аутентификация
        if($this->basic_auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->basic_auth);
        }

        # Не проводить проверку SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        # Timeout
        curl_setopt ($ch, CURLOPT_TIMEOUT, $this->timeout);

        #dd($this->post);

        # Данные POST
        if($this->post) {
            # Отправка в формате JSON
            if($this->post_format == 'json') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                $post = json_encode($this->post, JSON_UNESCAPED_UNICODE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . mb_strlen($post)
                ]);
            }

            # Отправка в нативном формате
            else {
                $post = http_build_query($this->post);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }
        }

        $this->response = curl_exec($ch);
        $this->code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
    }

    # Обработка запроса
    function responseHandler()
    {
        $errors = [
            301 => 'Moved permanently',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        # В случае пермонентного редиректа
        if($this->code == 301 && $this->follow) {
            # Получить последнюю ссылку редиректов
            $this->followUrl();
            # Повторный запрос
            $this->execCurlQuery();
            # Повторная обработка
            $this->responseHandler();
            return;
        }

        # Запрос завершился неудачей
        if(!$this->code || !$this->response || ($this->code != 200 && $this->code != 204)) {

            if(!$this->response) {
                $this->error = 'Пустой ответ';
            } else {
                $this->error = ($this->code)?$errors[$this->code] ?? $this->code:'Данные не доступны';
            }

            $this->cliEcho('Ошибка: '.$this->error);

            # Если повториения закончены
            if($this->query_attemps_now === 0) {
                $this->error = "Критическая ошибка подключения код {$this->code}: {$this->error} использовано попыток: {$this->query_attemps}";
                $this->cliEcho($this->error);
                if($this->die_on_failure) {
                    die;
                } else {
                    $this->query_attemps_now = null;
                    return;
                }
            }

            $this->cliEcho("Повторный запрос, осталось: {$this->query_attemps_now}, пауза {$this->time_dalay} сек.");

            # Уменьшение попыток
            $this->query_attemps_now--;

            # Пауза между повторениями
            if($this->time_dalay) sleep($this->time_dalay);

            # Повторный запрос
            $this->execCurlQuery();

            # Повторная обработка
            $this->responseHandler();
            return;
        }

        # Преобразовать ответ из JSON
        if($this->response_format == 'json') {
            $this->output = json_decode($this->response, 1);
            return;
        }


        # Преобразовать ответ из XML
        if($this->response_format == 'xml') {
            $xml = @simplexml_load_string($this->response);
            if(!$xml) {
                $this->error = 'xml parsing error';
                $this->cliEcho($this->error);
                return;
            }

            $json = json_encode($xml, JSON_UNESCAPED_UNICODE);

            $this->output = json_decode($json, 1);
            return;
        }

        $this->output = $this->response;
    }

    private function followUrl()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        curl_close( $ch );
        $headers = explode("\n", $response);

        $redir = $this->url;
        $j = count($headers);
        for($i=0;$i<$j;$i++){
            if(strpos($headers[$i],"Location:") !== false){
                $redir = trim(str_replace("Location:","", $headers[$i]));
                break;
            }
        }
        $this->url = $redir;
    }

    private function cliEcho($text) {
        if(!$this->cli_log) return;
        $time = date('H:i:s');
        echo "[$time] $text\n";
    }
}
