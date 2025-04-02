<?php namespace Zen\GroupTours\Store;

class TestStore extends Store
{
    public function get(array $data): string
    {
        return "Тестовые данные получены, входные параметры = {$data['input_data']}";
    }
}
