<?php namespace Zen\Worker\Console\volga;

/**
 * Консервативная нормализация текстовых сегментов маршрута Volga.
 *
 * Составной сегмент разбивается только тогда, когда он полностью покрывается
 * уже известными названиями городов. Неоднозначные служебные строки не должны
 * автоматически становиться новыми городами.
 */
class VolgaTownNormalizer
{
    private $knownNames = [];
    private $knownNamesByLength = [];

    public function __construct(array $knownNames)
    {
        foreach ($knownNames as $name) {
            $name = $this->normalizeWhitespace((string) $name);
            if ($name === '' || $this->isPollutedKnownName($name)) {
                continue;
            }

            $key = mb_strtolower($name, 'UTF-8');
            $this->knownNames[$key] = $name;
        }

        $this->knownNamesByLength = array_values($this->knownNames);
        usort($this->knownNamesByLength, function ($left, $right) {
            return mb_strlen($right, 'UTF-8') <=> mb_strlen($left, 'UTF-8');
        });
    }

    /**
     * Возвращает одно или несколько канонических имён.
     * Пустой результат означает, что сегмент неоднозначен и создавать город нельзя.
     */
    public function resolveSegment(string $raw): array
    {
        $normalized = $this->normalizeSegment($raw);
        if ($normalized === '') {
            return [];
        }

        $exact = $this->knownNames[mb_strtolower($normalized, 'UTF-8')] ?? null;
        if ($exact !== null) {
            return [$exact];
        }

        $knownParts = $this->splitByKnownTownNames($normalized);
        if (count($knownParts) >= 2) {
            return $knownParts;
        }

        if ($this->isSuspiciousRawSegment($raw)) {
            return [];
        }

        return [$normalized];
    }

    public function normalizeSegment(string $raw): string
    {
        $name = str_replace(['⏹', '⏴', '⏵'], ['-', '(', ')'], $raw);
        $name = preg_replace('/\([^)]*\)/u', ' ', $name);
        $name = str_replace(['«', '»', '"', "'", '„', '“'], '', $name);
        $name = preg_replace('/(?:^|\s)трансфер(?:\s|$)/ui', ' ', $name);
        $name = preg_replace('/\s+Уикэнд[!.]*\s*$/ui', '', $name);

        return $this->normalizeWhitespace($name);
    }

    /**
     * Greedy longest-match. Результат возвращается только при полном покрытии строки.
     */
    private function splitByKnownTownNames(string $name): array
    {
        $remaining = $name;
        $result = [];

        while ($remaining !== '') {
            $match = null;

            foreach ($this->knownNamesByLength as $knownName) {
                $length = mb_strlen($knownName, 'UTF-8');
                $prefix = mb_substr($remaining, 0, $length, 'UTF-8');
                if (mb_strtolower($prefix, 'UTF-8') !== mb_strtolower($knownName, 'UTF-8')) {
                    continue;
                }

                $next = mb_substr($remaining, $length, 1, 'UTF-8');
                if ($next !== '' && !preg_match('/^[\s,-]$/u', $next)) {
                    continue;
                }

                $match = $knownName;
                break;
            }

            if ($match === null) {
                return [];
            }

            $result[] = $match;
            $remaining = ltrim(
                mb_substr(
                    $remaining,
                    mb_strlen($match, 'UTF-8'),
                    mb_strlen($remaining, 'UTF-8'),
                    'UTF-8'
                ),
                " \t\n\r\0\x0B-,"
            );
        }

        return $result;
    }

    private function isSuspiciousRawSegment(string $raw): bool
    {
        return (bool) preg_match(
            '/[(),+!]|трансфер|уикэнд|экскурс|ноч[её]в|дн(?:я|ей)\b/ui',
            str_replace(['⏴', '⏵'], ['(', ')'], $raw)
        );
    }

    private function isPollutedKnownName(string $name): bool
    {
        return (bool) preg_match('/[(),+!]|трансфер|уикэнд|экскурс|ноч[её]в/ui', $name);
    }

    private function normalizeWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
