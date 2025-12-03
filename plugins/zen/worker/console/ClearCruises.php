<?php namespace Zen\Worker\Console;

use Illuminate\Console\Command;
use Zen\Worker\Controllers\Admin;
use October\Rain\Exception\ApplicationException;

class ClearCruises extends Command
{
    protected $name = 'worker:clear-cruises';
    protected $description = 'Очистка базы данных круизов';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $this->info('Начало очистки базы данных круизов...');

        try {
            Admin::clearCruises();
            $this->info('✓ База данных круизов успешно очищена');
        } catch (ApplicationException $ex) {
            $this->error('✗ Ошибка: ' . $ex->getMessage());
            return 1;
        } catch (\Exception $ex) {
            $this->error('✗ Неожиданная ошибка: ' . $ex->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}

