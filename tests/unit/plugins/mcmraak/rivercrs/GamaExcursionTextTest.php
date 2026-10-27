<?php

use Mcmraak\Rivercrs\Classes\GamaExcursionText;
use PHPUnit\Framework\TestCase;

class GamaExcursionTextTest extends TestCase
{
    const PHRASE = '(не входит в стоимость путевки и приобретается в офисах продаж или на борту теплохода у дирекции круиза):';

    public function testSplitsVariantsAndStylesPaidPhrase()
    {
        $html = GamaExcursionText::format(
            'Обзорная по городу — 1600 руб. Основная экскурсия ' . self::PHRASE . ' Прогулка по набережной'
            . ' | Карабиха — 3000 руб. Дополнительная экскурсия ' . self::PHRASE . ' Усадьба'
        );

        $this->assertNotContains(' | ', $html);
        $this->assertContains('<br><br>', $html);
        $this->assertContains('<b>Обзорная по городу — 1600 руб.</b>', $html);
        $this->assertContains('<b><em>Основная экскурсия ' . self::PHRASE . '</em></b><br>Прогулка по набережной', $html);
        $this->assertContains('<b>Карабиха — 3000 руб.</b>', $html);
        $this->assertContains('<br>Усадьба', $html);
    }

    public function testKeepsInitialsInsidePricedTitle()
    {
        $html = GamaExcursionText::format(
            'Посещение усадьбы Н.А. Некрасова «Карабиха» — 3000 руб. Дополнительная экскурсия '
            . self::PHRASE . ' Дом поэта'
        );

        $this->assertContains('<b>Посещение усадьбы Н.А. Некрасова «Карабиха» — 3000 руб.</b>', $html);
        $this->assertContains('<br>Дом поэта', $html);
    }

    public function testAcceptsRetailInsertAndCase()
    {
        $html = GamaExcursionText::format(
            'Пешеходная — 900 руб. Основная экскурсия (Не входит в стоимость путевки и приобретается в офисах продаж пакетом или в розницу или на борту теплохода у дирекции круиза): Встреча с хлебом'
        );

        $this->assertContains(
            '<b><em>Основная экскурсия (Не входит в стоимость путевки и приобретается в офисах продаж пакетом или в розницу или на борту теплохода у дирекции круиза):</em></b>',
            $html
        );
        $this->assertContains('<br>Встреча с хлебом', $html);
    }

    public function testLeavesIncludedExcursionPlainAfterTitle()
    {
        $html = GamaExcursionText::format(
            'Обзорная по городу — 2400 руб. Основная экскурсия (входит в стоимость путевки ): Осмотр усадьбы'
        );

        $this->assertSame(
            '<b>Обзорная по городу — 2400 руб.</b><br>Основная экскурсия (входит в стоимость путевки ): Осмотр усадьбы',
            $html
        );
    }

    public function testSplitsUnpricedSentenceWithoutCuttingInitials()
    {
        $html = GamaExcursionText::format('Усадьба Н.А. Некрасова. Дальше про дом');

        $this->assertSame('<b>Усадьба Н.А. Некрасова.</b><br>Дальше про дом', $html);
    }

    public function testEmptyStaysEmpty()
    {
        $this->assertSame('', GamaExcursionText::format(''));
        $this->assertSame('', GamaExcursionText::format(null));
        $this->assertSame('', GamaExcursionText::format('   |   '));
    }

    public function testEscapesMarkup()
    {
        $html = GamaExcursionText::format('Обзорная <script> — 10 руб. Текст & ещё');

        $this->assertContains('<b>Обзорная &lt;script&gt; — 10 руб.</b>', $html);
        $this->assertContains('Текст &amp; ещё', $html);
        $this->assertNotContains('<script>', $html);
    }
}
