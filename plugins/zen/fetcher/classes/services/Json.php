<?php

namespace Zen\Fetcher\Classes\Services;

/**
 * Система хранения данных в json
 * fetcher()->json("comparisons.cabins")->set([...]); - Сохранить данные
 * fetcher()->json("comparisons.cabins")->get(); - Вернуть массив
 * fetcher()->json("comparisons.cabins")->collect(); - Вернуть коллекцию
 */

class Json
{
    private string $jsonPath;

    public function __construct(string $path)
    {
        $path = str_replace('.', '/', $path);
        $this->jsonPath = base_path("plugins/zen/fetcher/data/json/$path.json");
    }

    public function set(array $data)
    {
        fetcher()->files()->chekFileDir($this->jsonPath);

        file_put_contents(
            $this->jsonPath,
            fetcher()->toJson($data, true, true)
        );
    }

    public function get()
    {
        return fetcher()->fromJson(
            file_get_contents(
                $this->jsonPath
            )
        );
    }

    public function collect()
    {
        return collect($this->get());
    }
}
