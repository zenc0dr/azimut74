<?php namespace Zen\Worker\Console\infoflot;

use Illuminate\Console\Command;
use Zen\Worker\Console\infoflot\InfoflotApiClient;
use Zen\Worker\Console\infoflot\InfoflotDatabase;
use Zen\Worker\Console\infoflot\InfoflotDataProcessor;
use Zen\Worker\Classes\ProcessLog;

/**
 * Скрипт для проверки обработки конкретного теплохода
 */
class CheckSingleShip extends Command
{
    protected $signature = 'worker:infoflot-check-ship 
                            {ship_id : ID теплохода в API Infoflot}
                            {--api-key= : API ключ Infoflot}';

    protected $description = 'Проверка обработки конкретного теплохода на фазе 1';

    private $apiKey = 'b5262f5d8de5be65b201bb5e3f5e544a245b6082';
    private $apiClient;
    private $db;

    public function handle()
    {
        $shipId = (int)$this->argument('ship_id');
        $apiKey = $this->option('api-key') ?: $this->apiKey;

        $this->info("🔍 Проверка теплохода ID: $shipId");
        $this->info('');

        $this->apiClient = new InfoflotApiClient($apiKey);
        $this->db = new InfoflotDatabase();

        // 1. Получаем информацию о теплоходе из API
        $this->info("📡 Шаг 1: Получение данных теплохода из API...");
        $ship = $this->getShipFromApi($shipId);
        
        if (!$ship) {
            $this->error("❌ Теплоход с ID $shipId не найден в API");
            return 1;
        }

        $shipName = $ship['name'] ?? 'N/A';
        $shipType = $ship['type'] ?? 'N/A';
        
        $this->info("✅ Теплоход найден: $shipName (Тип: $shipType)");
        $this->info('');

        // 2. Проверяем фильтрацию
        $this->info("🔍 Шаг 2: Проверка фильтрации...");
        $isFiltered = $this->checkFiltering($ship);
        if ($isFiltered) {
            $this->warn("⚠️ Теплоход будет отфильтрован (морское судно)");
            return 0;
        }
        $this->info("✅ Теплоход не будет отфильтрован");
        $this->info('');

        // 3. Получаем круизы из API
        $this->info("📡 Шаг 3: Получение круизов из API...");
        $cruises = $this->getCruisesFromApi($shipId);
        
        if (empty($cruises)) {
            $this->warn("⚠️ Круизы не найдены в API для теплохода $shipId");
            return 0;
        }

        $this->info("✅ Найдено круизов: " . count($cruises));
        $this->info('');

        // 4. Обрабатываем круизы (как в фазе 1)
        $this->info("⚙️ Шаг 4: Обработка круизов (имитация фазы 1)...");
        $this->processCruises($ship, $cruises);
        $this->info('');

        // 5. Проверяем результаты в SQLite
        $this->info("📊 Шаг 5: Проверка результатов в SQLite...");
        $this->checkSqliteResults($shipId);
        
        return 0;
    }

    private function getShipFromApi($shipId)
    {
        try {
            // Получаем все теплоходы и ищем нужный
            $page = 1;
            $limit = 100;
            
            while (true) {
                $response = $this->apiClient->getShips($page, $limit);
                
                if (!isset($response['data']) || !is_array($response['data'])) {
                    break;
                }

                foreach ($response['data'] as $ship) {
                    if ((int)$ship['id'] === $shipId) {
                        return $ship;
                    }
                }

                // Проверяем следующую страницу
                if (!isset($response['pagination']['pages']['next'])) {
                    break;
                }

                $page++;
                if ($page > 10) break; // Защита
            }

            return null;
        } catch (\Exception $e) {
            $this->error("Ошибка получения теплохода: " . $e->getMessage());
            return null;
        }
    }

    private function checkFiltering($ship)
    {
        $shipName = $ship['name'] ?? '';
        $shipType = $ship['type'] ?? '';
        
        $this->line("  Название: $shipName");
        $this->line("  Тип: $shipType");
        
        // Проверяем фильтры (как в InfoflotDataProcessor)
        if (stripos($shipType, 'лайнер') !== false || 
            stripos($shipName, 'MSC') !== false ||
            stripos($shipName, 'Celebrity') !== false ||
            stripos($shipName, 'Royal Caribbean') !== false ||
            stripos($shipName, 'Allure') !== false ||
            stripos($shipName, 'Anthem') !== false ||
            stripos($shipName, 'Freedom') !== false ||
            stripos($shipName, 'Harmony') !== false ||
            stripos($shipName, 'Independence') !== false ||
            stripos($shipName, 'Jewel') !== false ||
            stripos($shipName, 'Liberty') !== false ||
            stripos($shipName, 'Brilliance') !== false ||
            stripos($shipName, 'Costa') !== false) {
            return true;
        }
        
        return false;
    }

    private function getCruisesFromApi($shipId)
    {
        try {
            $cruises = [];
            $page = 1;
            $limit = 500;
            $date = date('Y-m-d');

            while (true) {
                $response = $this->apiClient->getCruisesByShip($shipId, $page, $limit, $date);
                
                if (!$response || !isset($response['data'])) {
                    break;
                }

                $cruisesPage = $response['data'];
                if (!is_array($cruisesPage) || empty($cruisesPage)) {
                    break;
                }

                $cruises = array_merge($cruises, $cruisesPage);
                
                // Проверяем следующую страницу
                if (!isset($response['pagination']['pages']['next'])) {
                    break;
                }

                $page++;
                if ($page > 10) break; // Защита
            }

            return $cruises;
        } catch (\Exception $e) {
            $this->error("Ошибка получения круизов: " . $e->getMessage());
            return [];
        }
    }

    private function processCruises($ship, $cruises)
    {
        $shipId = (int)$ship['id'];
        $shipName = $ship['name'] ?? 'N/A';
        
        // Сохраняем теплоход
        try {
            $this->db->saveShip($shipId, $shipName, $ship['type'] ?? '');
            $this->info("  ✅ Теплоход сохранён в SQLite");
        } catch (\Exception $e) {
            $this->warn("  ⚠️ Ошибка сохранения теплохода: " . $e->getMessage());
        }

        $savedCruises = 0;
        $savedPrices = 0;
        $skippedNoPrices = 0;
        $skippedPast = 0;

        foreach ($cruises as $cruise) {
            if (!is_array($cruise)) {
                continue;
            }

            $cruiseId = isset($cruise['id']) ? (int)$cruise['id'] : null;
            if (!$cruiseId) {
                continue;
            }

            // Проверяем дату начала
            $dateStart = null;
            if (isset($cruise['dateStartTimestamp'])) {
                $timestamp = is_numeric($cruise['dateStartTimestamp']) ? (int)$cruise['dateStartTimestamp'] : null;
                if ($timestamp) {
                    $dateStart = date('Y-m-d H:i:s', $timestamp);
                }
            } elseif (isset($cruise['dateStart']) && is_string($cruise['dateStart'])) {
                $dateStart = $cruise['dateStart'];
            }

            if ($dateStart && strtotime($dateStart) < time()) {
                $skippedPast++;
                continue;
            }
            
            if (!$dateStart) {
                // Пропускаем круизы без даты начала
                continue;
            }

            // Получаем цены для круиза
            try {
                $pricesData = $this->apiClient->getCruiseCabins($cruiseId);
                
                if (!$pricesData || empty($pricesData['prices']) || empty($pricesData['cabins'])) {
                    $skippedNoPrices++;
                    continue;
                }

                // Сохраняем круиз
                try {
                    $cruiseName = '';
                    if (isset($cruise['route']) && is_string($cruise['route'])) {
                        $cruiseName = $cruise['route'];
                    } elseif (isset($cruise['name']) && is_string($cruise['name'])) {
                        $cruiseName = $cruise['name'];
                    } else {
                        $cruiseName = 'Круиз ' . $cruiseId;
                    }
                    
                    $dateEnd = null;
                    if (isset($cruise['dateEndTimestamp'])) {
                        $timestamp = is_numeric($cruise['dateEndTimestamp']) ? (int)$cruise['dateEndTimestamp'] : null;
                        if ($timestamp) {
                            $dateEnd = date('Y-m-d H:i:s', $timestamp);
                        }
                    } elseif (isset($cruise['dateEnd']) && is_string($cruise['dateEnd'])) {
                        $dateEnd = $cruise['dateEnd'];
                    }
                    
                    $route = null;
                    if (isset($cruise['route']) && is_string($cruise['route'])) {
                        $route = $cruise['route'];
                    }

                    $cruiseData = [
                        'infoflot_cruise_id' => $cruiseId,
                        'infoflot_ship_id' => $shipId,
                        'name' => $cruiseName,
                        'beautiful_name' => $cruise['beautifulName'] ?? null,
                        'route' => $route ?? '',
                        'route_short' => $cruise['routeShort'] ?? null,
                        'date_start' => $dateStart,
                        'date_end' => $dateEnd,
                        'date_start_timestamp' => isset($cruise['dateStartTimestamp']) && is_numeric($cruise['dateStartTimestamp']) ? (int)$cruise['dateStartTimestamp'] : null,
                        'date_end_timestamp' => isset($cruise['dateEndTimestamp']) && is_numeric($cruise['dateEndTimestamp']) ? (int)$cruise['dateEndTimestamp'] : null,
                        'days' => isset($cruise['days']) && is_numeric($cruise['days']) ? (int)$cruise['days'] : null,
                        'nights' => isset($cruise['nights']) && is_numeric($cruise['nights']) ? (int)$cruise['nights'] : null,
                        'description' => $cruise['description'] ?? null
                    ];
                    
                    $this->db->saveCruise($cruiseData);
                    $savedCruises++;

                    // Сохраняем палубы и каюты (используем метод из InfoflotDataProcessor)
                    try {
                        $processor = new InfoflotDataProcessor($this->apiClient, $this->db);
                        // Используем рефлексию для вызова приватного метода
                        $reflection = new \ReflectionClass($processor);
                        $method = $reflection->getMethod('processShipDecksAndCabins');
                        $method->setAccessible(true);
                        $method->invoke($processor, $shipId, $pricesData);
                    } catch (\Exception $e) {
                        // Игнорируем ошибки палуб/кают, но логируем для отладки
                        // $this->warn("  ⚠️ Ошибка обработки палуб/кают: " . $e->getMessage());
                    } catch (\Error $e) {
                        // Игнорируем PHP ошибки
                    }

                    // Сохраняем цены
                    $prices = $this->processPrices($cruiseId, $pricesData);
                    $savedPrices += count($prices);

                } catch (\Exception $e) {
                    $this->warn("  ⚠️ Ошибка сохранения круиза $cruiseId: " . $e->getMessage());
                }

            } catch (\Exception $e) {
                $this->warn("  ⚠️ Ошибка получения цен для круиза $cruiseId: " . $e->getMessage());
            }
        }

        $this->info("  📊 Результаты обработки:");
        $this->info("    Сохранено круизов: $savedCruises");
        $this->info("    Сохранено цен: $savedPrices");
        $this->info("    Пропущено (прошедшие даты): $skippedPast");
        $this->info("    Пропущено (нет цен): $skippedNoPrices");
    }

    private function processPrices($cruiseId, $pricesData)
    {
        $prices = [];
        
        if (!isset($pricesData['prices']) || !is_array($pricesData['prices'])) {
            return $prices;
        }

        foreach ($pricesData['prices'] as $typeId => $priceData) {
            if (!is_array($priceData)) {
                continue;
            }

            $typeId = (int)$typeId;
            $typeName = $priceData['type_name'] ?? '';

            // Проверяем структуру prices
            if (!isset($priceData['prices']) || !is_array($priceData['prices'])) {
                continue;
            }

            if (isset($priceData['prices']['main_bottom']) && 
                is_array($priceData['prices']['main_bottom']) &&
                isset($priceData['prices']['main_bottom']['adult'])) {
                $adultPrice = (int)$priceData['prices']['main_bottom']['adult'];
                
                // Получаем deck_id из кают
                $deckId = null;
                if (isset($pricesData['cabins'])) {
                    foreach ($pricesData['cabins'] as $cabin) {
                        if (is_array($cabin) && isset($cabin['type_id']) && (int)$cabin['type_id'] === $typeId) {
                            if (isset($cabin['deck_id'])) {
                                $deckId = (int)$cabin['deck_id'];
                            }
                            break;
                        }
                    }
                }

                try {
                    $this->db->savePrice($cruiseId, $typeId, $typeName, $adultPrice, $deckId);
                    $prices[] = ['type_id' => $typeId, 'price' => $adultPrice];
                } catch (\Exception $e) {
                    // Игнорируем ошибки сохранения цен
                }
            }
        }

        return $prices;
    }

    private function checkSqliteResults($shipId)
    {
        $pdo = $this->db->getPdo();

        // Проверяем теплоход
        $stmt = $pdo->prepare("SELECT id, name FROM ships WHERE id = ?");
        $stmt->execute([$shipId]);
        $ship = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($ship) {
            $this->info("  ✅ Теплоход найден в SQLite: {$ship['name']}");
        } else {
            $this->warn("  ⚠️ Теплоход НЕ найден в SQLite");
        }

        // Проверяем круизы
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cruises WHERE ship_id = ?");
        $stmt->execute([$shipId]);
        $cruisesCount = $stmt->fetchColumn();
        $this->info("  📊 Круизов в SQLite: $cruisesCount");

        // Проверяем цены
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM prices p JOIN cruises c ON p.cruise_id = c.id WHERE c.ship_id = ?");
        $stmt->execute([$shipId]);
        $pricesCount = $stmt->fetchColumn();
        $this->info("  💰 Цен в SQLite: $pricesCount");

        // Показываем примеры круизов
        if ($cruisesCount > 0) {
            $stmt = $pdo->prepare("SELECT id, name, date_start FROM cruises WHERE ship_id = ? LIMIT 5");
            $stmt->execute([$shipId]);
            $cruises = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->info("  📋 Примеры круизов:");
            foreach ($cruises as $cruise) {
                $this->line("    ID: {$cruise['id']} | {$cruise['name']} | {$cruise['date_start']}");
            }
        }
    }
}

