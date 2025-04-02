<?php namespace Zen\Worker\Classes;

use Zen\Worker\Classes\ProcessLog;

class Http
{
    public
        $url,
        $code, // Код ответа на запрос
        $format, // Предполагаемый формат ответа ex: json, xml, ...
        $json_query = false,
        $dataGet,
        $dataPost,
        $error,
        $max_curl_time = 10,
        $response;


    function setTimout($seconds)
    {
        $this->max_curl_time = intval($seconds);
        return $this;
    }

    function dataGet($array)
    {
        $this->dataGet = $array;
        return $this;
    }

    function dataPost($array) {
        $this->dataPost = $array;
        return $this;
    }

    function jsonPost($array)
    {
        $this->dataPost = $array;
        $this->json_query = true;
        return $this;
    }

    function query($url, $format = 'json')
    {
        $this->format = 'json';
        $this->url = $url;
        $this->curl();

        if(!$this->error) {
            $this->{$format.'Format'}();
        }

        return $this;
    }

    function jsonFormat()
    {
        $this->response = json_decode($this->response,1);
    }

    function xmlFormat()
    {
        $xml = @simplexml_load_string($this->response);
        $json = json_encode($xml, JSON_UNESCAPED_UNICODE);
        $this->response = json_decode($json, 1);
    }


    private function curl()
    {
        $url = $this->url;

        if($this->dataGet) {
            $url .= '?'.http_build_query($this->dataGet);
        }

        ProcessLog::add("Запрос: $url");

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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if($this->max_curl_time) {
            curl_setopt ($ch, CURLOPT_TIMEOUT, $this->max_curl_time);
        }

        if($this->dataPost) {
            if($this->json_query) {

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                $post = json_encode($this->dataPost, JSON_UNESCAPED_UNICODE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . mb_strlen($post)
                ]);
            } else {
                $post = http_build_query($this->post);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }
        }


        $this->response = curl_exec($ch);

        $this->code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        curl_close($ch);

        if($this->code == 301) {
            # Получить последнюю ссылку редиректов
            $this->followUrl();
            $this->curl();
            return;
        }

        # Запрос завершился неудачей
        if(!$this->code || !$this->response || ($this->code != 200 && $this->code != 204)) {
            if(!$this->response) {
                $this->error = 'Пустой ответ, url:'.$url;
            } else {
                $this->error = ($this->code) ? $errors[$this->code] ?? $this->code : 'Данные не доступны';
            }
        }
    }

    # Следует по редиректу заменяя ссылку
    private function followUrl()
    {
        //$this->log("Обнаружен редирект");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        curl_close( $ch );
        $headers = explode("\n", $response);

        $redirect = $this->url;
        $j = count($headers);
        for($i=0;$i<$j;$i++){
            if(strpos($headers[$i],"Location:") !== false){
                $redirect = trim(str_replace("Location:","", $headers[$i]));
                break;
            }
        }
        //$this->log("Ссылка изменена {$this->url} > {$redirect}");

        $this->url = $redirect;
    }
}
