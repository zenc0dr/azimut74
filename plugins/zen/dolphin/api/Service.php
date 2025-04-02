<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;
use DB;

class Service extends Core
{
    # Генерировать CSS бэкенда для режима разработки vue-приложения для бэкенда
    # http://azimut.dc/zen/dolphin/api/service:backendCss
    function backendCss()
    {
        $cache = $this->cache('dolphin.service');

        $cache_key = 'backend.assets.css';
        $css = $cache->get($cache_key);

        if(!$css) {
            $backend_page = file_get_contents(url('/').'/console');
            $pattern = '/ (href|src)="([^"]+)"/';
            preg_match_all($pattern, $backend_page, $matches);
            $links = array_unique($matches[2]);
            $css = '';
            foreach ($links as $link) {
                if(strpos($link, '.css')!== false) {
                    $css .= file_get_contents($link);
                };
            }
            $cache->put($cache_key, $css);
        }

        return response($css)->header('Content-Type', 'text/css');
    }

    # http://azimut.dc/zen/dolphin/api/service:frontendCss
    function frontendCss()
    {
        $css = file(base_path('plugins/zen/dolphin/storage/frontend_css_links.txt'));
        $out = [];
        foreach($css as $css_path) {
            $out[] = file_get_contents(base_path(trim($css_path)));
        }
        return response(join('', $out))->header('Content-Type', 'text/css');
    }

    # http://azimut.dc/zen/dolphin/api/service:streamDump?token=d328fea4a3662d38640d6c1eaecdd02f
    function streamDump()
    {
        $token = $this->input('token');
        if(!$token) die('Отсутсвует токен');
        $stream = $this->cache('dolphin.search')->get($token);
        if(!$stream) die('Отсутствует поток');
        $this->ddd($stream);
    }

    # http://azimut.dc/zen/dolphin/api/service:getAgreement
    function getAgreement()
    {
        $html = DB::table('mcmraak_blocks_offer')->find(1)->offertext;

        $this->json([
            'html' => $html
        ]);
    }
}
