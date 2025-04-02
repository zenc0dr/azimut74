<?php

namespace Zen\Quiz\Classes\Api;

class Cities
{
    # http://azimut.dc/zen/quiz/api/Cities:getList
    public function getList()
    {
        $cities = \DB::table('zen_quiz_cities')
            ->where('active',1)
            ->orderBy('sort_order','asc')
            ->get(['id', 'name'])->toArray();

        return [
            'success' => true,
            'cities' => $cities
        ];
    }


}
