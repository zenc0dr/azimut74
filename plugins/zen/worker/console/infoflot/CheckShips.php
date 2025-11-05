<?php namespace Zen\Worker\Console\infoflot;

use Illuminate\Console\Command;
use Zen\Worker\Console\infoflot\InfoflotApiClient;
use Zen\Worker\Classes\ProcessLog;

/**
 * Одноразовый скрипт для проверки наличия теплоходов в API Infoflot
 * и количества круизов для них
 */
class CheckShips extends Command
{
    protected $signature = 'worker:infoflot-check-ships 
                            {--api-key= : API ключ Infoflot}
                            {--all : Показать все теплоходы из API}';

    protected $description = 'Проверка наличия теплоходов в API Infoflot и количества круизов';

    private $apiKey = 'b5262f5d8de5be65b201bb5e3f5e544a245b6082';
    private $apiClient;

    // Список теплоходов для проверки
    private $shipsToCheck = [
        'Капитан Пушкарев',
        'Огни большого города',
        'Маленький принц',
        'Лунная соната',
        'Лебединое озеро',
        'Россия',
        'Иван Бунин',
        'Павел Миронов',
        'Хирург Разумовский'
    ];

    public function handle()
    {
        $this->info('🔍 Проверка теплоходов в API Infoflot...');
        $this->info('');

        // Получаем API ключ из опций или используем дефолтный
        $apiKey = $this->option('api-key') ?: $this->apiKey;
        $this->apiClient = new InfoflotApiClient($apiKey);

        // Получаем все теплоходы из API
        $allShips = $this->getAllShips();
        
        if (empty($allShips)) {
            $this->error('❌ Не удалось получить список теплоходов из API');
            return 1;
        }

        $this->info("✅ Найдено теплоходов в API: " . count($allShips));
        $this->info('');

        // Ищем нужные теплоходы
        $foundShips = [];
        $notFoundShips = [];

        foreach ($this->shipsToCheck as $shipName) {
            $found = $this->findShipByName($allShips, $shipName);
            if ($found) {
                $foundShips[$shipName] = $found;
            } else {
                $notFoundShips[] = $shipName;
            }
        }

        // Выводим результаты
        $this->displayResults($foundShips, $notFoundShips, $allShips);

        // Если запрошены все теплоходы
        if ($this->option('all')) {
            $this->displayAllShips($allShips);
        }

        return 0;
    }

    /**
     * Получение всех теплоходов из API
     */
    private function getAllShips()
    {
        $allShips = [];
        $page = 1;
        $limit = 100;

        $this->info("📡 Загрузка теплоходов из API (страница за страницей)...");

        while (true) {
            try {
                $response = $this->apiClient->getShips($page, $limit);
                
                if (!isset($response['data']) || !is_array($response['data'])) {
                    break;
                }

                $ships = $response['data'];
                if (empty($ships)) {
                    break;
                }

                $allShips = array_merge($allShips, $ships);
                
                $this->info("  Загружено страница $page: " . count($ships) . " теплоходов");

                // Проверяем, есть ли ещё страницы
                // Структура API: pagination.pages.next.number или null
                $hasNextPage = false;
                if (isset($response['pagination']['pages']['next'])) {
                    $nextPageInfo = $response['pagination']['pages']['next'];
                    if (is_array($nextPageInfo) && isset($nextPageInfo['number'])) {
                        $hasNextPage = true;
                        $page = (int)$nextPageInfo['number'];
                    } elseif (isset($response['pagination']['pages']['next']['number'])) {
                        $hasNextPage = true;
                        $page = (int)$response['pagination']['pages']['next']['number'];
                    }
                }
                
                if (!$hasNextPage) {
                    // Показываем статистику
                    if (isset($response['pagination']['records']['total'])) {
                        $total = $response['pagination']['records']['total'];
                        $this->info("  Всего теплоходов в API: $total");
                    }
                    break;
                }
                
                // Защита от бесконечного цикла
                if ($page > 100) {
                    $this->warn("  Достигнут лимит страниц (100), остановка");
                    break;
                }

            } catch (\Exception $e) {
                $this->error("  Ошибка при загрузке страницы $page: " . $e->getMessage());
                break;
            }
        }

        return $allShips;
    }

    /**
     * Поиск теплохода по названию (с учётом разных вариантов написания)
     */
    private function findShipByName($allShips, $searchName)
    {
        $searchNameLower = mb_strtolower(trim($searchName));
        
        foreach ($allShips as $ship) {
            if (!isset($ship['name'])) {
                continue;
            }

            $shipNameLower = mb_strtolower(trim($ship['name']));
            
            // Точное совпадение
            if ($shipNameLower === $searchNameLower) {
                return $ship;
            }
            
            // Частичное совпадение (если название содержит искомое)
            if (strpos($shipNameLower, $searchNameLower) !== false || 
                strpos($searchNameLower, $shipNameLower) !== false) {
                return $ship;
            }
        }

        return null;
    }

    /**
     * Получение количества круизов для теплохода
     */
    private function getCruisesCount($shipId)
    {
        try {
            $response = $this->apiClient->getCruisesByShip($shipId, 1, 500);
            
            if (!$response || !isset($response['data'])) {
                return 0;
            }

            $cruises = $response['data'];
            $count = count($cruises);

            // Если есть пагинация, пытаемся получить общее количество
            if (isset($response['pagination'])) {
                $total = $response['pagination']['total'] ?? $count;
                return $total;
            }

            return $count;
        } catch (\Exception $e) {
            return -1; // Ошибка
        }
    }

    /**
     * Вывод результатов проверки
     */
    private function displayResults($foundShips, $notFoundShips, $allShips)
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('📊 РЕЗУЛЬТАТЫ ПРОВЕРКИ');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('');

        // Найденные теплоходы
        if (!empty($foundShips)) {
            $this->info('✅ НАЙДЕННЫЕ ТЕПЛОХОДЫ:');
            $this->info('');
            
            $tableData = [];
            foreach ($foundShips as $searchName => $ship) {
                $shipId = $ship['id'] ?? 'N/A';
                $shipName = $ship['name'] ?? 'N/A';
                $shipType = $ship['type'] ?? ($ship['shipType'] ?? 'N/A');
                
                // Получаем количество круизов
                $this->info("  🔍 Проверка круизов для '$shipName' (ID: $shipId)...");
                $cruisesCount = $this->getCruisesCount($shipId);
                
                $cruisesInfo = $cruisesCount >= 0 ? $cruisesCount : 'Ошибка';
                $status = $cruisesCount > 0 ? '✅' : ($cruisesCount == 0 ? '⚠️' : '❌');
                
                $tableData[] = [
                    'search' => $searchName,
                    'name' => $shipName,
                    'id' => $shipId,
                    'type' => $shipType,
                    'cruises' => $cruisesInfo,
                    'status' => $status
                ];
            }

            // Выводим таблицу
            $headers = ['Искомое название', 'Найдено в API', 'ID', 'Тип', 'Круизов', 'Статус'];
            $rows = array_map(function($row) {
                return [
                    $row['search'],
                    $row['name'],
                    $row['id'],
                    $row['type'],
                    $row['cruises'],
                    $row['status']
                ];
            }, $tableData);

            $this->table($headers, $rows);
            $this->info('');
        }

        // Не найденные теплоходы
        if (!empty($notFoundShips)) {
            $this->warn('❌ НЕ НАЙДЕННЫЕ ТЕПЛОХОДЫ:');
            foreach ($notFoundShips as $shipName) {
                $this->warn("  • $shipName");
            }
            $this->info('');
        }

        // Статистика
        $this->info('📈 СТАТИСТИКА:');
        $this->info("  Всего проверено: " . count($this->shipsToCheck));
        $this->info("  Найдено: " . count($foundShips));
        $this->info("  Не найдено: " . count($notFoundShips));
        $this->info('');
    }

    /**
     * Вывод всех теплоходов из API
     */
    private function displayAllShips($allShips)
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('📋 ВСЕ ТЕПЛОХОДЫ ИЗ API (первые 50)');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('');

        $tableData = [];
        $displayCount = min(50, count($allShips));
        
        for ($i = 0; $i < $displayCount; $i++) {
            $ship = $allShips[$i];
            $tableData[] = [
                $ship['id'] ?? 'N/A',
                $ship['name'] ?? 'N/A',
                $ship['type'] ?? ($ship['shipType'] ?? 'N/A')
            ];
        }

        $this->table(['ID', 'Название', 'Тип'], $tableData);
        
        if (count($allShips) > 50) {
            $this->info('');
            $this->info("... и ещё " . (count($allShips) - 50) . " теплоходов");
        }
        $this->info('');
    }
}

