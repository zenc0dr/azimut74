<?php

namespace Zen\Fetcher\Classes\Sources;

use Http;

/* Данный клас унаследует любой источник, в нём содержатся вспомогательные и сервисные методы источников */

class SourceClass
{
    /* OCMS v3
    public function httpRequest(
        string $url,
        ?array $post = null,
        array $headers = []
    ) {
        if ($post === null) {
            $response = Http::withHeaders($headers)->get($url);
        } else {
            $response = Http::withHeaders($headers)->post($url, $post);
        }
        return $response->body();
    }
    */

    /*
     if($this->dataGet) {
            $url .= '?'.http_build_query($this->dataGet);
        }
     */

    public function httpRequest(
        string $url,
        ?array $post = null,
        array $headers = []
    ) {
        if ($post === null) {
            $options = null;
            if ($headers) {
                $options = function ($http) use ($headers) {
                    if ($headers) {
                        foreach ($headers as $key => $value) {
                            $http->header($key, $value);
                        }
                    }
                };
            }
            $response = Http::get($url, $options);
        } else {
            $options = function ($http) use ($post, $headers) {
                if ($headers) {
                    foreach ($headers as $key => $value) {
                        $http->header($key, $value);
                    }
                }
                $http->data($post);
            };
            $response = Http::post($url, $options);
        }

        dd($response);
    }
}
