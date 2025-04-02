<?php

namespace Zen\Master\Classes\Services;

use Zen\Master\Traits\SingletonTrait;

class Strings
{
    use SingletonTrait;

    /**
     * Метод обработки номера телефона
     * @param string|null $phone
     * @return string|null
     */
    public function handlePhone(?string $phone = null): ?string
    {
        if (!$phone === null) {
            return null;
        }
        $phone = trim($phone);
        if (!$phone) {
            return null;
        }
        $phone = preg_replace('/\D/', '', $phone);
        return '7' . substr($phone, 1);
    }
}
