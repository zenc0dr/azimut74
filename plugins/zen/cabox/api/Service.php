<?php namespace Zen\Cabox\Api;

use Zen\Cabox\Classes\Core;
use Zen\Cabox\Models\Storage;
use Zen\Cabox\Classes\Cabox;
use Illuminate\Support\Facades\Artisan;

class Service extends Core
{
    # http://azimut.dc/zen/cabox/api/Service@getStorageSize
    function getStorageSize()
    {
        $this->isAdminCheck();
        $storage_id = $this->input('storage_id');
        $storage = Storage::find($storage_id);

        $cache = new Cabox([
            'path' => $storage->path
        ]);

        $this->json($cache->storageSize());
    }

    # http://azimut.dc/zen/cabox/api/Service@getStorageItems?storage_id=1
    function getStorageItems()
    {
        $this->isAdminCheck();
        $storage_id = $this->input('storage_id');
        if(!$storage_id) die('Не указан storage_id');
        $storage = Storage::find($storage_id);
        $page = $this->input('page');
        $length = 50;
        $filters = $this->input('filters');

        if(!$page) $page = 1;

        $cache = $storage->data;

        $items = $cache->storageItems();

        $items = collect($items)->sortBy('time', SORT_REGULAR, true);

        if($filters['key'] || $filters['time']) {
            $items = $items->filter(function ($value) use ($filters) {
                $return = true;
                if($filters['key'] && strpos($value['key'], $filters['key']) === false) $return = false;
                if($filters['time'] && strpos($value['created_at'], $filters['time']) === false) $return = false;
                return $return;
            });
        }

        $offset = ($page - 1) * $length;
        $pages = intval(floor(count($items) / $length));

        $items = $items->toArray();

        $items = array_slice($items, $offset, $length);

        $output = [
            'storage_items' => $items,
            'page' => $page,
            'pages' => $pages
        ];

        $this->json($output);

    }

    function deleteItem()
    {
        $key = $this->input('key');
        $storage_id = $this->input('storage_id');

        $storage = Storage::find($storage_id);
        $cabox = $storage->data;
        $cabox->del($key);
    }

    function cleanStorage()
    {
        $storage_id = $this->input('storage_id');
        $storage = Storage::find($storage_id);
        $storage->purge();
        $this->json([
            'success' => true
        ]);
    }
}
