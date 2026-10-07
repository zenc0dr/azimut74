<?php

use PHPUnit\Framework\TestCase;
use Zen\Worker\Console\infoflot\InfoflotDatabase;

class InfoflotWaybillTest extends TestCase
{
    private function databaseWithoutConnection(): InfoflotDatabase
    {
        $reflection = new ReflectionClass(InfoflotDatabase::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    private function invokePrivate($object, string $method, array $arguments)
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }

    public function testUsesStructuredPointsAndKeepsExternalIdsAsMetadata()
    {
        $database = $this->databaseWithoutConnection();
        $points = [
            ['id' => 17, 'name' => 'Кострома'],
            ['id' => 1, 'name' => 'Нижний Новгород'],
        ];

        [$waybill, $source] = $this->invokePrivate(
            $database,
            'createCruiseWaybillData',
            [[
                'points_in_route' => $points,
                'route' => 'Кострома – Нижний Новгород',
                'route_short' => 'Кострома – Нижний Новгород',
            ]]
        );

        $this->assertSame('points_in_route', $source);
        $this->assertCount(2, $waybill);
        $this->assertNull($waybill[0]['town']);
        $this->assertSame(17, $waybill[0]['infoflot_point_id']);
        $this->assertSame('Кострома', $waybill[0]['town_name']);
        $this->assertSame(1, $waybill[0]['bold']);
        $this->assertSame(1, $waybill[1]['bold']);
    }

    public function testFallsBackToRouteWhenStructuredPointsAreMissing()
    {
        $database = $this->databaseWithoutConnection();

        [$waybill, $source] = $this->invokePrivate(
            $database,
            'createCruiseWaybillData',
            [[
                'points_in_route' => [],
                'route' => 'Казань – Москва',
            ]]
        );

        $this->assertSame('route_fallback', $source);
        $this->assertSame(['Казань', 'Москва'], array_column($waybill, 'town_name'));
    }

    public function testRestoresOrderAndRepeatedStopsFromRoute()
    {
        $database = $this->databaseWithoutConnection();

        [$waybill, $source] = $this->invokePrivate(
            $database,
            'createCruiseWaybillData',
            [[
                'points_in_route' => [
                    ['id' => 40, 'name' => 'Пермь'],
                    ['id' => 12, 'name' => 'Ярославль'],
                ],
                'route' => 'Ярославль – Пермь + программа – Ярославль',
                'route_short' => 'Ярославль – Пермь – Ярославль',
            ]]
        );

        $this->assertSame('points_in_route', $source);
        $this->assertSame(
            ['Ярославль', 'Пермь', 'Ярославль'],
            array_column($waybill, 'town_name')
        );
    }

    public function testRestoresRoundTripFromSingleUniquePoint()
    {
        $database = $this->databaseWithoutConnection();

        [$waybill, $source] = $this->invokePrivate(
            $database,
            'createCruiseWaybillData',
            [[
                'points_in_route' => [
                    ['id' => 11, 'name' => 'Москва'],
                ],
                'route' => 'Москва – Москва',
            ]]
        );

        $this->assertSame('points_in_route', $source);
        $this->assertSame(['Москва', 'Москва'], array_column($waybill, 'town_name'));
    }
}
