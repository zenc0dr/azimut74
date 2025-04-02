<?php

namespace Zen\Fetcher\Classes\Api\Streams;

class Dispatcher extends Api
{
    # http://zetaprint.vps/fetcher/api/streams.Dispatcher:run
    public function run()
    {
        fetcher()->console('Dispatcher.run');
    }

    # http://zetaprint.vps/fetcher/api/streams.Dispatcher:runPool?pool_code=gruzconsalt.amo&force=true
    public function runPool()
    {
        $pool_code = request('pool_code');
        $force = boolval(request('force'));
        $pool = fetcher()->models()->pool()->where('code', $pool_code)->first();
        if (!$pool) {
            return;
        }
        $pool->manager->run($force);
    }

    public function stopPool()
    {
        $pool_code = request('pool_code');
        $pool = fetcher()->models()->pool()->where('code', $pool_code)->first();
        if (!$pool) {
            return;
        }
        $pool->manager->stop();
    }

    # http://zetaprint.vps/fetcher/api/streams.Dispatcher:truncatePool?pool_code=gruzconsalt.amo
    public function truncatePool()
    {
        $pool_code = request('pool_code');
        $pool = fetcher()->models()->pool()->where('code', $pool_code)->first();
        if (!$pool) {
            return;
        }
        $pool->manager->truncatePool();
    }
}
