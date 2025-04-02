<?php namespace Zen\Dolphin\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Test extends Command
{
    protected $name = 'dolphin:test';
    protected $description = 'Тестирование модулей';

    private $tests = [
        'HttpTest'
    ];

    public function handle()
    {
        $selected_tests = $this->option('tests');
        if($selected_tests) {
            $selected_tests = explode('+', $selected_tests);
            foreach ($this->tests as $key => $testName) {
                if(!in_array($testName, $selected_tests)) {
                    unset($this->tests[$key]);
                }
            }
        }

        foreach ($this->tests as $test) {
            app("Zen\Dolphin\Tests\\$test")->go();
        }
    }

    protected function getOptions()
    {
        return [
            #ex: php artisan dolphin:test --tests=SearchTest
            ['tests', null, InputOption::VALUE_OPTIONAL, 'Выбор тестов', false],
        ];
    }
}
