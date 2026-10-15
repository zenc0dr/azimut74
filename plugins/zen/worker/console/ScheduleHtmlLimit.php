<?php namespace Zen\Worker\Console;

/**
 * Подгоняет HTML расписания под MySQL TEXT (65535 байт).
 * Короткие ячейки (дата, город, время) не трогает.
 * Длинный текст режет по последнему пробелу.
 */
class ScheduleHtmlLimit
{
    const LIMIT = 60000;
    const MIN_CELL = 80;

    public static function fit($html)
    {
        $html = (string) $html;
        if (strlen($html) <= self::LIMIT) {
            return $html;
        }

        $floor = self::MIN_CELL;
        $guard = 0;
        while (strlen($html) > self::LIMIT && $guard < 2000) {
            $guard++;
            if (!preg_match_all('/<td>([^<]*)<\/td>/u', $html, $matches, PREG_OFFSET_CAPTURE)) {
                break;
            }

            $target = null;
            foreach ($matches[1] as $cell) {
                $text = $cell[0];
                if (mb_strlen($text, 'UTF-8') < $floor) {
                    continue;
                }
                if ($target === null || strlen($text) > strlen($target['text'])) {
                    $target = [
                        'text' => $text,
                        'offset' => $cell[1],
                    ];
                }
            }

            if ($target === null) {
                if ($floor <= 40) {
                    break;
                }
                $floor = 40;
                continue;
            }

            $overflow = strlen($html) - self::LIMIT;
            $keepBytes = max($floor, strlen($target['text']) - max($overflow, (int) (strlen($target['text']) * 0.15)));
            $short = self::shorten($target['text'], $keepBytes);
            if ($short === $target['text']) {
                break;
            }

            $html = substr($html, 0, $target['offset'])
                . $short
                . substr($html, $target['offset'] + strlen($target['text']));
        }

        return $html;
    }

    private static function shorten($text, $keepBytes)
    {
        if (strlen($text) <= $keepBytes) {
            return $text;
        }

        $cut = self::cutBytes($text, $keepBytes);
        if (preg_match('/^(.*)\s+\S*$/u', $cut, $match) && strlen($match[1]) >= self::MIN_CELL) {
            $cut = $match[1];
        }

        $cut = preg_replace('/[\s.,;:—\-]+$/u', '', $cut);

        return $cut . '…';
    }

    private static function cutBytes($text, $keepBytes)
    {
        return mb_strcut($text, 0, $keepBytes, 'UTF-8');
    }
}
