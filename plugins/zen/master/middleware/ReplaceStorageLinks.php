<?php

namespace Zen\Master\Middleware;

use Illuminate\Http\Request;
use Closure;

class ReplaceStorageLinks
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof \Illuminate\Http\Response) {
            $content = $response->getContent();
            $content = $this->replaceLinks($content);
            $response->setContent($content);
        }

        return $response;
    }

    private function replaceLinks(string $html_content)
    {
        // Домен для замены
        $replacementDomain = 'https://azimut-storage.8ber.ru';

        // Регулярное выражение для замены абсолютных ссылок
        $patternAbsolute = '/\b(?:http:\/\/|https:\/\/|https:\/\/www\.)[a-zA-Z0-9.-]+(\/storage\/app\/[a-zA-Z0-9\/._-]+)/';
        $html_content = preg_replace_callback($patternAbsolute, function ($matches) use ($replacementDomain) {
            return $replacementDomain . $matches[1];
        }, $html_content);

        // Регулярное выражение для замены относительных ссылок
        $patternRelative = '/(?<![a-zA-Z0-9:\/])\/storage\/app\/([a-zA-Z0-9\/._-]+)/';
        $html_content = preg_replace_callback($patternRelative, function ($matches) use ($replacementDomain) {
            return $replacementDomain . '/storage/app/' . $matches[1];
        }, $html_content);

        return $html_content;
    }
}
