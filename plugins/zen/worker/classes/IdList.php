<?php namespace Zen\Worker\Classes;

class IdList
{
    /**
     * Разбор списка id из CLI: "1,2, 3" или одно число.
     *
     * @param mixed $value
     * @return int[]
     */
    public static function parse($value): array
    {
        if ($value === null || $value === '' || $value === false) {
            return [];
        }
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        }
        $ids = [];
        foreach ($parts as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    public static function join(array $ids): string
    {
        return implode(',', array_map('intval', $ids));
    }
}
