<?php

namespace Zen\Master\Api;

use Zen\Cleaner\Classes\SmartCleaner;

class Debug
{
    # http://azimut.dc/master.api.Debug.test
    public function test()
    {
        //master()->telegram()->sendMessage('Ваша лодка готова капитан!');
    }

    # http://azimut.dc/master.api.Debug.getFreeSize
    public function getFreeSize()
    {
        $size = shell_exec("df -m --output=avail / | tail -n 1");
        $size = trim($size);
        $size = intval($size);
        echo "<div style='font-size:30px'>$size Мб</div>";
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
