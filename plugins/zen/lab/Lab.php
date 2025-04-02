<?php namespace Zen\Lab;

use Zen\Lab\Classes\ZenImage;
use Zen\Lab\Classes\ZenValidator;

class Lab
{
    # Создать объект - Валидатор
    static function validator()
    {
        return new ZenValidator();
    }

    static function resize($image_path, $opts = [])
    {
        return (new ZenImage())->resize($image_path, $opts);
    }
}
