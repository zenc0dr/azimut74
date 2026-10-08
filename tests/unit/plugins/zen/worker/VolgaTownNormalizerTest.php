<?php

use PHPUnit\Framework\TestCase;
use Zen\Worker\Console\volga\VolgaTownNormalizer;

class VolgaTownNormalizerTest extends TestCase
{
    private function normalizer(): VolgaTownNormalizer
    {
        return new VolgaTownNormalizer([
            'Пермь',
            'Елабуга',
            'Казань',
            'Сарапул',
            'Екатеринбург',
            'Сергиев Посад',
            'Москва',
            'Ростов Великий',
            'Нижний Новгород',
            'Ростов-на-Дону',
            'Весьегонск',
        ]);
    }

    public function testSplitsFullyKnownCompositeSegment()
    {
        $this->assertSame(
            ['Елабуга', 'Казань'],
            $this->normalizer()->resolveSegment('Елабуга (трансфер) Казань Уикэнд!')
        );
    }

    public function testKeepsCanonicalMultiwordAndHyphenatedTowns()
    {
        $normalizer = $this->normalizer();

        $this->assertSame(
            ['Нижний Новгород'],
            $normalizer->resolveSegment('Нижний Новгород')
        );
        $this->assertSame(
            ['Ростов-на-Дону'],
            $normalizer->resolveSegment('Ростов-на-Дону')
        );
    }

    public function testSplitsKnownTownsJoinedBySourceHyphen()
    {
        $this->assertSame(
            ['Екатеринбург', 'Казань', 'Сарапул'],
            $this->normalizer()->resolveSegment('Екатеринбург (трансфер) Казань-Сарапул')
        );
    }

    public function testSplitsCommaSeparatedSegmentOnlyWhenFullyKnown()
    {
        $this->assertSame(
            ['Сергиев Посад', 'Москва', 'Ростов Великий'],
            $this->normalizer()->resolveSegment('Сергиев Посад, Москва (1 ночь), Ростов Великий')
        );
    }

    public function testRemovesParentheticalServiceTextForKnownTown()
    {
        $this->assertSame(
            ['Весьегонск'],
            $this->normalizer()->resolveSegment('Весьегонск (р.Молога)')
        );
    }

    public function testRejectsUnknownSuspiciousSegment()
    {
        $this->assertSame(
            [],
            $this->normalizer()->resolveSegment('Неизвестный город (экскурсия)')
        );
    }

    public function testAllowsSimpleUnknownTownForBackwardCompatibility()
    {
        $this->assertSame(
            ['Новый Порт'],
            $this->normalizer()->resolveSegment('Новый Порт')
        );
    }

    public function testCleansTechnicalAndExcursionLabels()
    {
        $normalizer = $this->normalizer();

        $this->assertSame(
            ['Казань'],
            $normalizer->resolveSegment('Казань (техн.)')
        );
        $this->assertSame(
            ['Сарапул'],
            $normalizer->resolveSegment('Сарапул, экскурсия в Ижевск')
        );
    }

    public function testRejectsExcursionProgramPoint()
    {
        $this->assertSame(
            [],
            $this->normalizer()->resolveSegment(
                'Экскурсионный тур Ярославль + Архангельск, Северодвинск, день 1'
            )
        );
    }
}
