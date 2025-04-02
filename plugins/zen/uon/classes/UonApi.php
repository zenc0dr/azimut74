<?php namespace Zen\Uon\Classes;

use Cache;

class UonApi extends Core
{
    private $api_url = 'https://api.u-on.ru/';
    private $key = '7hPDplm8q882V4K7pFbB';
    private $error = null;
    private $last_url = null;

    private $response;

    public function query($query)
    {
        $query = $this->api_url.$this->key . '/' . $query;
        $this->last_url = $query;

        $cache_key = md5($query);

        if (boolval(request()->get('cache'))) {
            if ($response = Cache::get($cache_key)) {
                return $response;
            }
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $query,
            CURLOPT_POST => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($resp, 1);

        if (boolval(request()->get('cache'))) {
            Cache::add($cache_key, $response, 60);
        }

        return $response;
    }

    public function getException()
    {
        $this->error = "Запрос: {$this->last_url} вернул ошибку.";
    }

    public function getLastUrl()
    {
        return $this->last_url;
    }

    public function getArray()
    {
        return $this->response;
    }

    public function getJson($return = 0)
    {
        return $this->json($this->response, $return);
    }

    public function dump()
    {
        dd($this->response);
    }

    public function queryWrap($code, $id)
    {
        $response = $this->query("$code/$id.json");

        #if($id == '15638') dd($this->last_url, $response);

        //dd($this->last_url, $response);

        if (!$response) {
            $this->getException();
            return;
        }

        if (!@$response[$code][0]) {
            if (@$response['error']) {
                die($response['error']['message']);
            } else {
                dd($response);
            }
        }

        $this->response = $response[$code][0];
    }


    public function getRequest($request_id)
    {
        $this->queryWrap('request', $request_id);
        return $this;
    }

    public function getTourist($user_id)
    {
        $this->queryWrap('user', $user_id);
        return $this;
    }
}
