<?php namespace Zen\Dolphin\Console;

use Illuminate\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Zen\Dolphin\Classes\Parser;
use Zen\Dolphin\Classes\Core;


class Go extends Command
{
    protected $name = 'dolphin:go';
    protected $description = 'Получение данных из API Dolphin';

    public function handle()
    {
        $parserClass = new Parser;
        $selected_parsers = $this->option('parsers');
        $parser_class_names = $parserClass->getActualParserNames($selected_parsers);

        if(!$parser_class_names) {
            $parserClass->cleanState();
            $parser_class_names = $parserClass->getActualParserNames($selected_parsers);
        }

        $core = new Core;

        foreach ($parser_class_names as $parser_class_name) {
            app("Zen\Dolphin\Parsers\\$parser_class_name")->go();
            $parserClass = new Parser;
            $core->log([
                'source' => "Zen\Dolphin\Parsers\\$parser_class_name",
                'text' => 'Парсер завершил работу',
                'dump' => $parserClass->getState($parser_class_name)
            ]);
        }

        $core->notice()->sendMail([
            'subject' => 'Парсеры завершили работу',
            'html' => 'Всё прошло удачно, более точная информация появится чуть позже.'
        ]);
    }

    protected function getOptions()
    {
        return [
            #ex: php artisan dolphin:go --parsers=Areas+Rooms
            ['parsers', null, InputOption::VALUE_OPTIONAL, 'Выбор парсеров', false],
        ];
    }
}
