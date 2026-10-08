<?php

use PHPUnit\Framework\TestCase;
use Zen\Worker\Console\volga\VolgaRouteClient;

class VolgaRouteClientTest extends TestCase
{
    public function testMergesSimpleAndCompositeRoutes()
    {
        $type1 = <<<'XML'
<VolgaML><track>
  <cruise id="20232">
    <TrackPoint TrackId="1" Point="Пермь" Arrival=" " Departure="2027-05-07 08:00" StayTime="Посадка"/>
    <TrackPoint TrackId="2" Point="Елабуга" Arrival="2027-05-08 14:00" Departure=" " StayTime="Высадка"/>
  </cruise>
  <cruise id="18005"/>
</track></VolgaML>
XML;

        $type2 = <<<'XML'
<VolgaML><track>
  <cruise id="18005">
    <TrackPoint TrackId="3" Point="Казань" Arrival=" " Departure="2027-08-19 17:00" StayTime="Посадка"/>
    <TrackPoint TrackId="4" Point="Самара" Arrival="2027-08-20 10:00" Departure=" " StayTime="Высадка"/>
  </cruise>
</track></VolgaML>
XML;

        $routes = VolgaRouteClient::mergeXml($type1, $type2);

        $this->assertSame(
            ['Пермь', 'Елабуга'],
            array_column($routes[20232], 'point')
        );
        $this->assertSame(
            ['Казань', 'Самара'],
            array_column($routes[18005], 'point')
        );
        $this->assertSame(2, $routes[18005][0]['tracking_type']);
        $this->assertSame(3, $routes[18005][0]['track_id']);
    }
}
