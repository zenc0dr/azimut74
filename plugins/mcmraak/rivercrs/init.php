<?php

if (!function_exists('pricePatch')) {
    function pricePatch(): \Mcmraak\Rivercrs\Patches\DeckPricesPatch
    {
        return \Mcmraak\Rivercrs\Patches\DeckPricesPatch::getInstance();
    }
}
