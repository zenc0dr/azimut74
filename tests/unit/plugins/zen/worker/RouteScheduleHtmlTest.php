<?php

use PHPUnit\Framework\TestCase;
use Zen\Worker\Console\RouteScheduleHtml;

class RouteScheduleHtmlTest extends TestCase
{
    public function testRendersVolgaStopTimes()
    {
        $html = RouteScheduleHtml::render([
            RouteScheduleHtml::fromVolgaPoint([
                'town_name' => 'Пермь',
                'arrival' => '',
                'departure' => '2027-05-07 08:00',
                'stay_time' => 'Посадка',
            ]),
            RouteScheduleHtml::fromVolgaPoint([
                'town_name' => 'Елабуга',
                'arrival' => '2027-05-08 14:00',
                'departure' => '2027-05-08 17:30',
                'stay_time' => '03:30:00',
            ]),
        ]);

        $this->assertContains('Пермь', $html);
        $this->assertContains('08:00', $html);
        $this->assertContains('210', $html);
        $this->assertNotContains('Посадка', $html);
    }

    public function testIncludesInfoflotStopDescription()
    {
        $html = RouteScheduleHtml::fromInfoflotTimetable([
            [
                'place' => 'Кострома',
                'dateArrival' => '2026-10-07 16:00:00',
                'dateDeparture' => '2026-10-07 18:00:00',
                'description' => '<b>Посадка</b>',
                'excursions' => null,
            ],
            [
                'place' => 'Нижний Новгород',
                'dateArrival' => '2026-10-08 14:30:00',
                'dateDeparture' => '2026-10-08 18:30:00',
                'description' => 'Обзорная экскурсия',
                'excursions' => null,
            ],
        ]);

        $this->assertContains('Обзорная экскурсия', $html);
        $this->assertContains('Посадка', $html);
        $this->assertNotContains('<b>', $html);
    }
}
