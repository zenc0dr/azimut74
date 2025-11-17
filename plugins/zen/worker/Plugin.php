<?php namespace Zen\Worker;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {

    }

    public function registerSettings()
    {

    }

    function register()
    {
        $this->registerConsoleCommand('worker:go', 'Zen\Worker\Console\Go');
        $this->registerConsoleCommand('worker:gama-parse', 'Zen\Worker\Console\gama\GamaParse');
        $this->registerConsoleCommand('worker:infoflot-parse', 'Zen\Worker\Console\infoflot\InfoflotParse');
        $this->registerConsoleCommand('worker:infoflot-check-ships', 'Zen\Worker\Console\infoflot\CheckShips');
        $this->registerConsoleCommand('worker:infoflot-check-ship', 'Zen\Worker\Console\infoflot\CheckSingleShip');
        $this->registerConsoleCommand('worker:volga-parse', 'Zen\Worker\Console\volga\VolgaParse');
        $this->registerConsoleCommand('worker:germes-parse', 'Zen\Worker\Console\germes\GermesParse');
        $this->registerConsoleCommand('worker:waterway-parse', 'Zen\Worker\Console\waterway\WaterwayParse');
        $this->registerConsoleCommand('worker:clear-cache', 'Zen\Worker\Console\ClearCache');
    }

    public function boot()
    {
        // Подключаем хелпер функцию cursor()
        require_once __DIR__ . '/init.php';
    }

    public function registerReportWidgets()
    {
        return [
            'Zen\Worker\ReportWidgets\ClearCruisesWidget' => [
                'label'   => 'Очистка базы круизов',
                'context' => 'dashboard'
            ],
            'Zen\Worker\ReportWidgets\CacheStatsWidget' => [
                'label'   => 'Статистика кеша парсеров',
                'context' => 'dashboard'
            ]
        ];
    }
}
