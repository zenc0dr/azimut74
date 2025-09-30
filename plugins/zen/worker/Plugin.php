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
        $this->registerConsoleCommand('worker:gama-test', 'Zen\Worker\Console\gama\GamaTest');
        $this->registerConsoleCommand('worker:gama-view', 'Zen\Worker\Console\gama\GamaViewer');
        $this->registerConsoleCommand('worker:gama-check-imported-cruises', 'Zen\Worker\Console\gama\GamaCheckImportedCruises');
        $this->registerConsoleCommand('worker:gama-clean', 'Zen\Worker\Console\gama\GamaClean');
    }
}
