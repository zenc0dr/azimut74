<?php namespace Mcmraak\Rivercrs\Classes;

/**
 * Точки маршрута, которые не относятся к Российской Федерации.
 * Используется только виджетом поиска: записи в БД не удаляются и не меняются.
 *
 * Рауталахти сознательно не в списке: это стоянка на российских рейсах
 * теплохода «Россия» (Ладога), а не зарубежный порт.
 */
class RussiaPlaces
{
    const FILTER_CACHE_KEY = 'rivercrs.FilterDATA.ru';

    /** @var array<string,bool>|null */
    protected static $outside;

    public static function isOutsideRussia($name)
    {
        $raw = self::lower(trim((string) $name));
        if ($raw === '') {
            return false;
        }

        // «Сочи (Россия)» и похожие подписи остаются российскими.
        if (mb_strpos($raw, 'росси', 0, 'UTF-8') !== false) {
            return false;
        }

        foreach (array('турц', 'египет', 'итал', 'израил', 'грец', 'мальт') as $hint) {
            if (mb_strpos($raw, $hint, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        $canonical = self::canonical($raw);
        $outside = self::outsideMap();

        return isset($outside[$canonical]);
    }

    protected static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    protected static function canonical($lowerName)
    {
        $name = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $lowerName);
        $name = preg_replace('/\s+/u', ' ', $name);

        return trim($name);
    }

    /**
     * @return array<string,bool>
     */
    protected static function outsideMap()
    {
        if (self::$outside !== null) {
            return self::$outside;
        }

        $names = array(
            // Турция
            'аланья',
            'амасра',
            'бодрум',
            'бозджаада',
            'гечек',
            'дальян',
            'демре',
            'измир',
            'калкан',
            'каш',
            'каякёй',
            'кушадасы',
            'мармарис',
            'патара',
            'самсун',
            'стамбул',
            'трабзон',
            'фетхие',
            'бухта байиндир',
            'бухта гёккая',
            'бухта саманлик или бончуклу',
            'залив скопея',
            'залив фирназ',
            'остров гемилер',
            'остров кекова',
            'острова яссика',
            // Греция
            'афон',
            'волос',
            'закинтос',
            'кавала',
            'катаколон',
            'крит',
            'миконос',
            'нафплион',
            'пирей',
            'родос',
            'салоники',
            'санторини',
            // Италия, Египет, Израиль, Мальта
            'александрия',
            'ашдод',
            'катания',
            'мальта',
            'мессина',
            'неаполь',
            'палермо',
            'чивитавеккья',
            // Беларусь (круизы «Белой Руси»)
            'брест',
            'дубое',
            'качановичи',
            'кобрин',
            'пинск',
            'стахово',
        );

        self::$outside = array();
        foreach ($names as $name) {
            self::$outside[$name] = true;
        }

        return self::$outside;
    }
}
