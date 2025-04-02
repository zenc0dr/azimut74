<?php namespace Zen\Dolphin\Classes;

use Zen\Dolphin\Classes\Core;
use Yaml;

class Parser extends Core
{
    public $parser_class_name; # Имя класса обработчика
    public $parser_name;       # Имя обработчика человеческое
    public $iterations_count;   # Счётчик операций,
    public $progress_count; # Прогресс

    private $state;

    # Список классов парсеров
    private array $parsers = [
        'TrashCleaner',
        'Areas',
        'Rooms',
        'RoomCategories',
        'Pansions',
        'HotelContent',
        'Tours',
    ];

    public function __construct()
    {
        $this->loadState();
    }

    public function initCounts($iterations_count)
    {
        $this->iterations_count = $iterations_count;
        $this->progress_count = 0;
    }

    public function saveProgress($custom_text = null)
    {
        if ($custom_text) {
            $this->putHandlerState($custom_text);
            return;
        }

        $this->progress_count++;
        $value = "{$this->parser_class_name} - Обработано: {$this->progress_count} из {$this->iterations_count}";
        $this->putHandlerState($value);
    }

    public function parserSuccess()
    {

        if ($this->iterations_count) {
            $value = "{$this->parser_class_name} - Обработано: {$this->iterations_count} записей";
        } else {
            $value = "{$this->parser_class_name} - Задача выполнена";
        }

        $this->putHandlerState($value, true);
    }

    private function putHandlerState($value, $success = false)
    {
        $this->state['progress'][$this->parser_class_name] = [
            'name' => $this->parser_name,
            'time' => date('d.m.Y H:i:s'),
            'value' => $value,
            'success' => $success
        ];
        $this->saveState();
    }

    private function getDefaultState()
    {
        return ['progress' => [], 'status' => []];
    }

    # Загрузить состояние
    private function loadState()
    {
        $state_path = storage_path('logs/dolphin-state.yaml');

        if (!file_exists($state_path)) {
            $empty_state = $this->getDefaultState();
            file_put_contents($state_path, Yaml::render($empty_state));
            $this->state = $empty_state;
        } else {
            $this->state = Yaml::parse(file_get_contents($state_path));
        }
    }

    # Сохранить состояние
    private function saveState()
    {
        $this->state['status']['last_time'] = time();
        $state_path = storage_path('logs/dolphin-state.yaml');
        file_put_contents($state_path, Yaml::render($this->state));
    }

    # Получить состояние
    public function getState($parser_name = null)
    {
        if ($parser_name) {
            return $this->state['progress'][$parser_name];
        }

        return $this->state;
    }

    public function isSuccess($parser_name)
    {
        if (@$this->state['progress'][$parser_name]['success']) {
            return true;
        }
        return false;
    }

    # Получить список актуальных для обработки парсеров
    public function getActualParserNames($selected_names = null)
    {
        if ($selected_names) {
            return explode('+', $selected_names);
        }

        $exist_classes = [];
        foreach ($this->parsers as $parser) {
            if ($this->isSuccess($parser)) {
                continue;
            }
            if (file_exists(base_path("plugins/zen/dolphin/parsers/$parser.php"))) {
                $exist_classes[] = $parser;
            }
        }

        return $exist_classes;
    }

    # Обнулить результаты для повторного полного цикла
    public function cleanState()
    {
        unlink(storage_path('logs/dolphin-state.yaml'));
        $this->loadState();
    }
}
