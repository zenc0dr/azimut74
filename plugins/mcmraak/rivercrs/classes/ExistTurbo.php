<?php

namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Db;

class ExistTurbo
{
    private Checkin $checkin;

    public function request(Checkin $checkin, $options = [])
    {
        $this->checkin = $checkin;
        $dataset = $this->innerQuery();
    }

    public function innerQuery()
    {
        $dataset = DB::table('mcmraak_rivercrs_pricing as pricing')
            ->where('pricing.checkin_id', $this->checkin->id)
            ->join(
                'mcmraak_rivercrs_checkins as checkin',
                'checkin.id',
                '=',
                'pricing.checkin_id'
            )
            ->join(
                'mcmraak_rivercrs_cabins as cabin',
                'cabin.id',
                '=',
                'pricing.cabin_id'
            )
            ->join(
                'mcmraak_rivercrs_decks_pivot as decks_pivot',
                'decks_pivot.cabin_id',
                '=',
                'cabin.id'
            )
            ->join(
                'mcmraak_rivercrs_decks as deck',
                'deck.id',
                '=',
                'decks_pivot.deck_id'
            )
            ->select(
                'deck.id as deck_id',
                'deck.name as deck_name',
                'cabin.id as cabin_id',
                'cabin.category as cabin_name',
                'cabin.places_main_count as cabin_main_places',
                'cabin.places_extra_count as cabin_extra_places',
                'pricing.price_a as price_value',
                'pricing.price_b as price2_value',
                'deck.sort_order as deck_order'
            )
            ->get()->toArray();

        dd($dataset);
    }
}
