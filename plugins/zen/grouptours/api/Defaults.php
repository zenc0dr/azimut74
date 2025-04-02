<?php namespace Zen\GroupTours\Api;

use BackendAuth;

class Defaults extends Api
{
    # http://azimut.dc/zen/gt/api/defaults:get
    public function get()
    {
        $data = [
            'geo_tree' => $this->store('GeoTree'),
            'tags' => $this->store('Tags'),
            'admin' => BackendAuth::check(),
            'results_limit' => $this->setting('results_limit', 10),
        ];

        $this->json($data);
    }
}
