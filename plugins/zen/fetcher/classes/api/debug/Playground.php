<?php

namespace Zen\Fetcher\Classes\Api\Debug;

use Zen\Fetcher\Classes\Sources\SourceClass;

class Playground extends Api
{
    # http://azimut.dc/fetcher/api/debug.Playground:test
    public function test()
    {
        $source = new SourceClass();
        $get = '?key=b5262f5d8de5be65b201bb5e3f5e544a245b6082&page=1&limit=100';
        dd(
            $source->httpRequest(
                'https://restapi.infoflot.com/ships' . $get
            )
        );
    }
}
