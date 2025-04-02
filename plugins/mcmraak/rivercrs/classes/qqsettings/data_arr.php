<?php

$prices = $cabinRenderData['prices'];
$href = (function($checkin) {
    $q=explode('/',request()->url());
    return $q[0].'//'.$q[2]."/russia-river-cruises/cruise/$checkin->id";
})($checkin);

$arr = [];
foreach ($prices as $price) {
    $arr[] = json_encode([
        'checkinDt' => date('d.m.Y', strtotime($checkin->date)),
        'nights' => $checkin->days - 1,
        'hotelName' => $checkin->motorship->name,
        'roomType' => $this->renderRoomType($price),
        'boardType' => '3-х разовое',
        'price' => $price['price_value'],
        'currency' => 'RUB',
        'city_from' => '',
        'occupancy' => [
            'adultsCount' => 1,
            'childrenCount' => 0,
            'childAges' => '',
        ],
        'region' => Mcmraak\Rivercrs\Models\Waybills::GetWaybillPath($checkin->id,1,0, '-'), // Маршрут
        'country' => 'Россия',
        'thumbnail' => $checkin->motorship->getAvatar(),
        'excursion' => true,
        'href' => $href
    ], JSON_UNESCAPED_UNICODE);
}
