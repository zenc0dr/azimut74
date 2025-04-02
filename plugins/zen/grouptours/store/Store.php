<?php namespace Zen\GroupTours\Store;

use Zen\GroupTours\Classes\Core;

abstract class Store extends Core
{
    # Тут можно добавлять только уникальные для данного паттерна методы
    # Общие для всех методы находятся в Core

    # В любом дочернем классе Store должен быть реазиован метод get()
    abstract public function get(array $data);
}
