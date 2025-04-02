<?php

namespace Zen\Fetcher\Classes\Api\Streams;

class State extends Api
{
    # http://zetaprint.vps/fetcher/api/streams.State:all
    public function all(): string
    {
        $pools = fetcher()
            ->models()
            ->pool()
            ->active()
            ->get();

        $output = [];
        foreach ($pools as $pool) {
            $states = $pool->manager->state();
            $pool_is_active = collect($states)->first(fn($item) => $item['process']);
            $output[] = [
                'code' => $pool->code,
                'name' => $pool->name,
                'states' => $states,
                'in_process' => $pool_is_active
            ];
        }

        return fetcher()
            ->toJson([
                'pools' => $output
            ], true, true);
    }

    # http://zetaprint.vps/fetcher/api/streams.State:get?code=gruzconsalt.amo
    public function get(): string
    {
        return fetcher()->toJson(fetcher()->pool(request('code'))->state());
    }
}
