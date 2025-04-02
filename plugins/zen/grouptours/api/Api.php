<?php namespace Zen\GroupTours\Api;

use Zen\GroupTours\Classes\Core;
use Input;

abstract class Api extends Core
{
    # Тут можно добавлять только уникальные для данного паттерна методы
    # например посредники или валидаторы
    # Общие для всех методы находятся в Core
    public function input($key)
    {
        return Input::get($key);
    }
}
