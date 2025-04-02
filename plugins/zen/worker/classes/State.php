<?php namespace Zen\Worker\Classes;

use Zen\Worker\Classes\Stream;
use Yaml;

class State
{
    private $stream, $pools, $path, $data;

    function __construct(Stream $stream)
    {
        $this->stream = $stream;
        $this->path = storage_path("worker/{$stream->model->code}State.yaml");
        $this->pools = $this->stream->model->pools;
        $this->loadState();
        unset($this->stream);
    }

    function loadState() {
        if(!file_exists($this->path)) $this->createState();
        $this->data = Yaml::parseFile($this->path);
    }

    function saveState() {
        $state = Yaml::render($this->data);
        file_put_contents($this->path, $state);
    }

    function createState()
    {
        $dir_path = dirname($this->path);

        if(!file_exists($dir_path)) mkdir($dir_path, 0777, true);

        $state = [];

        $pools_count = count($this->pools);
        for($i=0;$i<$pools_count;$i++) {
            $state[] = [
                'progress_of' => null,
                'progress_to' => null,
                'errors_count' => 0,
                'success' => false,
                'updated_at' => null
            ];
        }

        $state = Yaml::render($state);
        file_put_contents($this->path, $state);
    }

    function getActualPoolState()
    {
        $i = 0;
        foreach ($this->pools as $pool_record) {
            $pool = array_merge($pool_record, $this->data[$i]);
            $pool['index'] = $i;
            $pool = (object) $pool;
            $i++;
            if($pool->success) continue;
            if(!$pool->active) continue;
            return $pool;
        }

        return false;
    }

    function updatePoolState($pool_state, $update_data = null)
    {
        if($update_data) {
            $this->data[$pool_state->index] = array_merge($this->data[$pool_state->index], $update_data);
        } else {
            $this->data[$pool_state->index]['progress_to']++;
        }

        $update_data['updated_at'] = time();

        $this->saveState();
    }
}
