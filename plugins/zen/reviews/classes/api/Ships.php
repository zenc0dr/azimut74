<?php

namespace Zen\Reviews\Classes\Api;

class Ships
{
    # http://azimut.dc/reviews/api/Ships:getList
    public function getList()
    {
        $reviews = reviews()->db('mcmraak_rivercrs_motorships')->get(['id', 'name'])->toArray();
        foreach ($reviews as $review) {
            $review->name = trim(preg_replace(array("/Теплоход/", '/"/', "/\(.*\)/"), '', $review->name));
        }

        return reviews()->toJson([
            'success' => true,
            'ships' => $reviews
        ]);
    }


}
