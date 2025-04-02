<?php

namespace Zen\GroupTours\Api;
use Zen\GroupTours\Models\Tour;

class Search extends Api
{
    # http://azimut.dc/zen/gt/api/search:go?debug={"geo_objects":[],"days":[],"tags":[],"type":"catalog"}
    public function go()
    {
        $data = $this->input('data');
        $debug = $this->input('debug');

        if ($debug) {
            $data = json_decode('{"geo_objects":[],"days":[],"tags":[],"type":"catalog"}', 1);
        }

        $results = $this->store('Search', $data);

        if ($debug) {
            dd($results);
        }

        $this->json([
            'results' => $results,
            'success' => true
        ]);
    }
}
