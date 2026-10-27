<?php namespace Mcmraak\Rivercrs\Classes;

/**
 * Оформление текста экскурсий Гамы для вкладки «Маршрут».
 * На вход — plain text ячейки (варианты через « | »), на выход — узкий HTML.
 */
class GamaExcursionText
{
    public static function formatSchedule(array $schedule)
    {
        foreach ($schedule as $index => $day) {
            if (!is_array($day) || !array_key_exists('desc', $day)) {
                continue;
            }
            $schedule[$index]['desc'] = self::format($day['desc']);
        }

        return $schedule;
    }

    public static function format($text)
    {
        $text = html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '' || preg_match('/^(?:\|\s*)+$/u', $text)) {
            return '';
        }

        $blocks = [];
        foreach (explode(' | ', $text) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $blocks[] = self::formatOne($part);
        }

        return implode('<br><br>', $blocks);
    }

    private static function formatOne($text)
    {
        list($title, $rest) = self::splitTitle($text);
        $html = '<b>' . self::escape($title) . '</b>';
        if ($rest === '') {
            return $html;
        }

        $phraseEnd = self::paymentPhraseEnd($rest);
        if ($phraseEnd === null) {
            return $html . '<br>' . self::escape($rest);
        }

        $label = trim(substr($rest, 0, $phraseEnd));
        $after = trim(substr($rest, $phraseEnd));
        $html .= '<br><b><em>' . self::escape($label) . '</em></b>';
        if ($after !== '') {
            $html .= '<br>' . self::escape($after);
        }

        return $html;
    }

    private static function splitTitle($text)
    {
        if (preg_match('/^(.*?(?:—|–|-)\s*\d+\s*руб\.)\s*(.*)$/us', $text, $match)) {
            return [trim($match[1]), trim($match[2])];
        }

        $length = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $length; $i++) {
            if (mb_substr($text, $i, 1, 'UTF-8') !== '.') {
                continue;
            }
            if (self::isInitialPeriod($text, $i)) {
                continue;
            }
            $title = trim(mb_substr($text, 0, $i + 1, 'UTF-8'));
            $rest = trim(mb_substr($text, $i + 1, $length - $i - 1, 'UTF-8'));

            return [$title, $rest];
        }

        return [trim($text), ''];
    }

    private static function isInitialPeriod($text, $index)
    {
        if ($index < 1) {
            return false;
        }
        $prev = mb_substr($text, $index - 1, 1, 'UTF-8');
        if (!preg_match('/\p{L}/u', $prev)) {
            return false;
        }
        if ($index < 2) {
            return true;
        }
        $before = mb_substr($text, $index - 2, 1, 'UTF-8');

        return !preg_match('/\p{L}/u', $before);
    }

    /**
     * Конец фразы «(не входит в стоимость … дирекции круиза):», в байтах.
     * Допускает лишние пробелы, регистр и вставку «пакетом или в розницу».
     */
    private static function paymentPhraseEnd($text)
    {
        $pattern = '/\('
            . '\s*не\s+входит\s+в\s+стоимость\s+пут[её]вки\s+и\s+приобретается\s+в\s+офисах\s+продаж'
            . '(?:\s+пакетом\s+или\s+в\s+розницу)?'
            . '\s+или\s+на\s+борту\s+теплохода\s+у\s+дирекции\s+круиза'
            . '\s*\)\s*:/iu';

        if (!preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        return $match[0][1] + strlen($match[0][0]);
    }

    private static function escape($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
