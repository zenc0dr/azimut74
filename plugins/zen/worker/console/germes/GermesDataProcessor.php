<?php namespace Zen\Worker\Console\germes;

use Mcmraak\Rivercrs\Classes\Getter;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class GermesDataProcessor
{
    private $db;
    private $apiClient;
    private $getter;
    private $timeout;
    private $limit;
    private $cabinCategories;
    private $cabinsPivot;

    public function __construct($database, $timeout = 30, $limit = null)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->db = $database;
        $this->apiClient = new GermesApiClient($timeout);
        $this->getter = new Getter();
        $this->timeout = $timeout;
        $this->limit = $limit;
    }

    /**
     * Обработка данных о теплоходах
     */
    public function processShipsData()
    {
        ProcessLog::add('Начинаем обработку теплоходов Germes...');
        
        $shipsData = $this->apiClient->getShips();
        
        if (!isset($shipsData['Теплоход'])) {
            ProcessLog::add("Нет данных о теплоходах");
            return;
        }
        
        $ships = [];
        $items = $shipsData['Теплоход'];
        
        // Обрабатываем случай, когда теплоход один элемент
        if (isset($items['@attributes'])) {
            $items = [$items];
        }
        
        foreach ($items as $item) {
            $data = $item['@attributes'] ?? $item;
            $ships[] = [
                'id' => (int)$data['id'],
                'name' => $data['Название'] ?? ''
            ];
        }
        
        if (!empty($ships)) {
            $this->db->saveShipsBatch($ships);
            ProcessLog::add("Сохранено теплоходов: " . count($ships));
        }
    }

    /**
     * Обработка данных о классах кают
     */
    public function processCabinCategoriesData()
    {
        ProcessLog::add('Начинаем обработку классов кают Germes...');
        
        $categoriesData = $this->apiClient->getCabinCategories();
        
        if (!isset($categoriesData['Класс'])) {
            ProcessLog::add("Нет данных о классах кают");
            return;
        }
        
        $categories = [];
        $items = $categoriesData['Класс'];
        
        // Обрабатываем случай, когда класс один элемент
        if (isset($items['@attributes'])) {
            $items = [$items];
        }
        
        foreach ($items as $item) {
            $data = $item['@attributes'] ?? $item;
            $description = null;
            
            if (isset($item['Описание'])) {
                $description = is_array($item['Описание']) 
                    ? join('', $item['Описание']) 
                    : $item['Описание'];
            }
            
            // Название находится на верхнем уровне элемента, а не в @attributes
            $name = $item['Название'] ?? '';
            
            // Используем id_teplohod из @attributes для установки ship_id
            $shipId = isset($data['id_teplohod']) ? (int)$data['id_teplohod'] : null;
            
            $categories[] = [
                'id' => (int)$data['id'],
                'name' => $name,
                'description' => $description,
                'ship_id' => $shipId // Используем id_teplohod из API
            ];
        }
        
        if (!empty($categories)) {
            $this->db->saveCabinCategoriesBatch($categories);
            ProcessLog::add("Сохранено классов кают: " . count($categories));
        }
        
        // Сохраняем для использования в обработке цен
        $this->cabinCategories = $categoriesData;
    }

    /**
     * Обработка данных о каютах (pivot)
     */
    public function processCabinsData()
    {
        ProcessLog::add('Начинаем обработку кают (pivot) Germes...');
        
        $pivotData = $this->apiClient->getCabinsPivot();
        
        if (!isset($pivotData['Kauta'])) {
            ProcessLog::add("Нет данных о каютах (pivot)");
            return;
        }
        
        $cabins = [];
        $items = $pivotData['Kauta'];
        
        // Обрабатываем случай, когда каюта одна
        if (isset($items['@attributes'])) {
            $items = [$items];
        }
        
        foreach ($items as $item) {
            $data = $item['@attributes'] ?? $item;
            // number - это физический номер каюты (202, 301, 401...), а не внутренний ID
            $cabinNumber = isset($data['number']) ? (int)$data['number'] : null;
            $cabins[] = [
                'id' => (int)$data['id'],
                'cabin_category_id' => (int)$data['idClassKauta'],
                'number' => $cabinNumber
            ];
        }
        
        if (!empty($cabins)) {
            $this->db->saveCabinsBatch($cabins);
            ProcessLog::add("Сохранено кают (pivot): " . count($cabins));
        }
        
        // Сохраняем для использования в обработке цен
        $this->cabinsPivot = $pivotData;
    }

    /**
     * Обработка круизов
     */
    public function processCruisesData()
    {
        ProcessLog::add('Начинаем обработку круизов Germes...');
        
        $cruisesData = $this->apiClient->getCruises();
        
        if (!isset($cruisesData['тур'])) {
            ProcessLog::add("Нет данных о круизах");
            return;
        }
        
        $cruises = [];
        $items = $cruisesData['тур'];
        
        // Обрабатываем случай, когда круиз один элемент
        if (isset($items['@attributes'])) {
            $items = [$items];
        }
        
        // Применяем лимит если указан
        if ($this->limit) {
            $items = array_slice($items, 0, $this->limit);
            ProcessLog::add("⚠️  Ограничение парсинга: обрабатываем только {$this->limit} круизов");
        }
        
        foreach ($items as $item) {
            $cruiseData = $this->prepareCruiseData($item);
            if ($cruiseData) {
                $cruises[] = $cruiseData;
            }
        }
        
        if (!empty($cruises)) {
            $this->db->saveCruisesBatch($cruises);
            ProcessLog::add("Сохранено круизов: " . count($cruises));
        }
        
        // Обрабатываем цены для каждого круиза
        $this->processCruisesPrices($cruises);
        
        // Обновляем ship_id в cabin_categories на основе данных из круизов и цен (для тех, у кого не было id_teplohod)
        ProcessLog::add('Обновление связей категорий кают с теплоходами...');
        $updated = $this->db->updateCabinCategoriesShipId();
        ProcessLog::add("Обновлено связей категорий кают с теплоходами: $updated");
        
        // Обновляем палубы из CSV файлов (приоритет над парсингом описаний)
        ProcessLog::add('Обновление палуб из CSV файлов...');
        $csvDeckMapping = $this->updateDecksFromCsvFiles();
        if (!empty($csvDeckMapping)) {
            $updatedCsvDecks = $this->db->updateCabinCategoriesDeckId($csvDeckMapping);
            ProcessLog::add("Обновлено связей категорий кают с палубами из CSV: $updatedCsvDecks");
        }
        
        // Извлекаем палубы из описаний и обновляем deck_id (только для тех, у кого еще нет палубы)
        ProcessLog::add('Извлечение палуб из описаний кают...');
        $deckMapping = $this->extractDecksFromDescriptions();
        if (!empty($deckMapping)) {
            $updatedDecks = $this->db->updateCabinCategoriesDeckId($deckMapping);
            ProcessLog::add("Обновлено связей категорий кают с палубами из описаний: $updatedDecks");
        }
        
        // Удаляем категории без привязки к теплоходу
        ProcessLog::add('Удаление категорий кают без привязки к теплоходу...');
        $deleted = $this->db->deleteCabinCategoriesWithoutShip();
        ProcessLog::add("Удалено категорий без теплохода: $deleted");
    }

    /**
     * Подготовка данных круиза для сохранения
     */
    private function prepareCruiseData($cruise)
    {
        $cruiseId = $cruise['@attributes']['id'] ?? null;
        if (!$cruiseId) {
            return null;
        }
        
        $shipId = $cruise['Теплоход'] ?? null;
        if (!$shipId) {
            ProcessLog::add("Круиз $cruiseId: отсутствует ID теплохода");
            return null;
        }
        
        // Форматируем даты
        $dates = $this->formatGermesDates($cruise);
        
        // Получаем маршрут
        $waybill = $this->prepareWaybillData($cruise);
        
        return [
            'germes_cruise_id' => (int)$cruiseId,
            'germes_ship_id' => (int)$shipId,
            'name' => $cruise['Название'] ?? null,
            'route' => $cruise['Маршрут'] ?? null,
            'date_start' => $dates['date'],
            'date_end' => $dates['dateb'],
            'waybill_data' => $waybill ? json_encode($waybill, JSON_UNESCAPED_UNICODE) : null
        ];
    }

    /**
     * Форматирование дат Germes
     */
    private function formatGermesDates($cruise)
    {
        $d_a = $cruise['ДатаОтплытия'] ?? '';
        $d_a = $this->mutatorGermesDate($d_a);
        $t_a = $cruise['ВремяОтплытия'] ?? '00:00';
        $date = $d_a . ' ' . $t_a . ':00';

        $d_b = $cruise['ДатаПрибытия'] ?? '';
        $d_b = $this->mutatorGermesDate($d_b);
        $t_b = $cruise['ВремяПрибытия'] ?? '00:00';
        $dateb = $d_b . ' ' . $t_b . ':00';

        return [
            'date' => $date,
            'dateb' => $dateb,
        ];
    }

    /**
     * Мутатор даты из 08.05.2018 в 2018-05-08
     */
    private function mutatorGermesDate($date)
    {
        if (empty($date)) {
            return date('Y-m-d');
        }
        
        $i = explode('.', $date);
        if (count($i) !== 3) {
            return date('Y-m-d');
        }
        
        return $i[2] . '-' . $i[1] . '-' . $i[0];
    }

    /**
     * Подготовка данных маршрута
     */
    private function prepareWaybillData($cruise)
    {
        $cruiseId = $cruise['@attributes']['id'] ?? null;
        if (!$cruiseId) {
            return null;
        }
        
        // Получаем маршрут через API
        $trace = $this->apiClient->getCruiseTrace($cruiseId);
        
        if (!$trace || !isset($trace['Tour']['City'])) {
            ProcessLog::add("Круиз $cruiseId: не удалось получить маршрут");
            return null;
        }
        
        $towns = $trace['Tour']['City'];
        if (!is_array($towns)) {
            $towns = [$towns];
        }
        
        // Извлекаем bold города из HTML тегов <span> в поле Маршрут
        $boldTowns = [];
        $routeHtml = $cruise['Маршрут'] ?? '';
        if (!empty($routeHtml)) {
            preg_match_all('/<span[^>]+>(.+)<\/span>/', $routeHtml, $matches);
            if (!empty($matches[1])) {
                $boldTowns = $matches[1];
            }
        }
        
        $waybill = [];
        $key = 0;
        $max = count($towns) - 1;
        
        foreach ($towns as $townName) {
            // Получаем ID города через Getter
            $townId = $this->getter->getTownId($townName, 'germes');
            
            // Определяем, является ли город bold
            $isBold = false;
            if (!empty($boldTowns)) {
                $isBold = in_array($townName, $boldTowns) || $key == 0 || $key == $max;
            } else {
                $isBold = $key == 0 || $key == $max;
            }
            
            $waybill[] = [
                'town' => $townId,
                'town_name' => $townName,
                'excursion' => '',
                'bold' => $isBold ? 1 : 0
            ];
            
            $key++;
        }
        
        return $waybill;
    }

    /**
     * Обработка цен для всех круизов
     */
    private function processCruisesPrices($cruises)
    {
        ProcessLog::add('Начинаем обработку цен для круизов...');
        
        $totalCruises = count($cruises);
        $processed = 0;
        $batchSize = 50; // Обрабатываем по 50 круизов за раз
        $allPrices = [];
        
        for ($i = 0; $i < $totalCruises; $i += $batchSize) {
            $batch = array_slice($cruises, $i, $batchSize);
            $batchPrices = [];
            
            foreach ($batch as $cruise) {
                $prices = $this->processCruisePrices($cruise);
                if ($prices) {
                    $batchPrices = array_merge($batchPrices, $prices);
                }
                $processed++;
            }
            
            // Batch сохранение цен для этой группы
            if (!empty($batchPrices)) {
                $this->db->savePricesBatch($batchPrices);
                ProcessLog::add("📊 Сохранено цен: " . count($batchPrices) . " для круизов " . ($i + 1) . "-" . min($i + $batchSize, $totalCruises));
            }
            
            // Логируем прогресс
            ProcessLog::add("📊 Обработано круизов: $processed из $totalCruises");
            
            // Сбрасываем время выполнения
            set_time_limit(0);
            ini_set('max_execution_time', 0);
        }
        
        ProcessLog::add("✅ Обработка цен завершена: $processed из $totalCruises");
    }

    /**
     * Обработка цен для одного круиза
     */
    private function processCruisePrices($cruise)
    {
        $cruiseId = $cruise['germes_cruise_id'];
        $shipId = $cruise['germes_ship_id'];
        
        try {
            set_time_limit(0);
            ini_set('max_execution_time', 0);
            
            // Получаем цены через API
            $pricesData = $this->apiClient->getCruisePrices($cruiseId);
            
            if (!$pricesData || !isset($pricesData['Каюта'])) {
                ProcessLog::add("Нет данных о ценах для круиза $cruiseId");
                return [];
            }
            
            // Загружаем справочные данные, если ещё не загружены
            if (!$this->cabinCategories) {
                $this->cabinCategories = $this->apiClient->getCabinCategories();
            }
            if (!$this->cabinsPivot) {
                $this->cabinsPivot = $this->apiClient->getCabinsPivot();
            }
            
            $prices = [];
            $cabins = $pricesData['Каюта'];
            
            // Обрабатываем случай, когда каюта одна
            if (isset($cabins['@attributes'])) {
                $cabins = [$cabins];
            }
            
            foreach ($cabins as $germesPrice) {
                $priceId = $germesPrice['id'] ?? null;
                if (!$priceId) {
                    continue;
                }
                
                // Получаем класс каюты через pivot
                $cabinClass = $this->getGermesCabinClass($priceId, $this->cabinCategories, $this->cabinsPivot);
                if (!$cabinClass) {
                    continue;
                }
                
                // Проверяем, не является ли каюта "не сдаётся"
                $cabinName = $cabinClass['Название'] ?? '';
                if ($this->getter->isCabinNotLet($cabinName, $shipId)) {
                    continue;
                }
                
                $priceValue = (int)($germesPrice['ЦенаОснМест'] ?? 0);
                if (!$priceValue) {
                    continue;
                }
                
                $categoryId = $cabinClass['@attributes']['id'] ?? null;
                if (!$categoryId) {
                    continue;
                }
                
                $prices[] = [
                    'cruise_id' => $cruiseId,
                    'cabin_category_id' => (int)$categoryId,
                    'price_value' => $priceValue
                ];
            }
            
            if (!empty($prices)) {
                ProcessLog::add("Обработано цен для круиза $cruiseId: " . count($prices));
            }
            
            return $prices;
            
        } catch (Exception $e) {
            ProcessLog::add("Ошибка обработки цен для круиза $cruiseId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Обновление палуб из CSV файлов
     * CSV файлы находятся в папке ocms/plugins/zen/worker/data/rooms_decks
     * Формат имени файла: {germes_ship_id}_{ship_name}.csv
     * 
     * Структура CSV:
     * - Первая строка: названия палуб (колонки)
     * - Последующие строки: номера кают в соответствующих колонках
     * 
     * Логика:
     * 1. Загружаем CSV для каждого теплохода
     * 2. Создаем маппинг номер_каюты -> палуба
     * 3. Через pivot таблицу (cabins) связываем категории кают с палубами
     * 4. Возвращаем маппинг категория_id -> deck_id
     */
    private function updateDecksFromCsvFiles()
    {
        $csvDir = __DIR__ . '/../../data/rooms_decks';
        
        if (!is_dir($csvDir)) {
            ProcessLog::add("Папка с CSV файлами не найдена: $csvDir");
            return [];
        }
        
        // Получаем список всех теплоходов из SQLite
        $stmt = $this->db->getPdo()->query("SELECT id, name FROM ships");
        $ships = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $categoryToDeckMap = [];
        $processedShips = 0;
        $totalCabinsProcessed = 0;
        
        foreach ($ships as $ship) {
            $shipId = (int)$ship['id'];
            $shipName = $ship['name'];
            
            // Формируем имя файла: {ship_id}_{ship_name}.csv
            $csvFileName = $shipId . '_' . $shipName . '.csv';
            $csvFilePath = $csvDir . '/' . $csvFileName;
            
            if (!file_exists($csvFilePath)) {
                continue; // CSV файл для этого теплохода не найден
            }
            
            ProcessLog::add("Обработка CSV файла для теплохода $shipId ($shipName)...");
            
            // Загружаем CSV и создаем маппинг номер_каюты -> палуба
            $cabinToDeckMap = $this->loadCsvDeckMapping($csvFilePath);
            
            if (empty($cabinToDeckMap)) {
                ProcessLog::add("  CSV файл пуст или некорректен");
                continue;
            }
            
            // Связываем категории кают с палубами через pivot таблицу
            $categoryDeckMapping = $this->mapCategoriesToDecksFromCabins($shipId, $cabinToDeckMap);
            
            if (!empty($categoryDeckMapping)) {
                foreach ($categoryDeckMapping as $categoryId => $deckName) {
                    // Сохраняем палубу в базу
                    $deckId = $this->db->saveDeck($deckName);
                    
                    if ($deckId) {
                        $categoryToDeckMap[$categoryId] = $deckId;
                    }
                }
                
                $processedShips++;
                $totalCabinsProcessed += count($cabinToDeckMap);
                ProcessLog::add("  Обработано кают: " . count($cabinToDeckMap) . ", категорий с палубами: " . count($categoryDeckMapping));
            }
        }
        
        if ($processedShips > 0) {
            ProcessLog::add("Обработано CSV файлов: $processedShips, всего кают: $totalCabinsProcessed");
        } else {
            ProcessLog::add("CSV файлы не найдены или пусты");
        }
        
        return $categoryToDeckMap;
    }
    
    /**
     * Загрузка маппинга номер_каюты -> палуба из CSV файла
     * 
     * @param string $csvFilePath Путь к CSV файлу
     * @return array Маппинг [номер_каюты => название_палубы]
     */
    private function loadCsvDeckMapping($csvFilePath)
    {
        $handle = @fopen($csvFilePath, 'r');
        if (!$handle) {
            return [];
        }
        
        // Читаем заголовки (названия палуб)
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return [];
        }
        
        $cabinToDeckMap = [];
        
        // Читаем строки с номерами кают
        while (($row = fgetcsv($handle)) !== false) {
            foreach ($row as $colIndex => $cabinNumber) {
                // Пропускаем пустые ячейки
                if (empty($cabinNumber) || !is_numeric(trim($cabinNumber))) {
                    continue;
                }
                
                $cabinNumber = (int)trim($cabinNumber);
                $deckName = isset($header[$colIndex]) ? trim($header[$colIndex]) : '';
                
                if (!empty($deckName)) {
                    $cabinToDeckMap[$cabinNumber] = $deckName;
                }
            }
        }
        
        fclose($handle);
        
        return $cabinToDeckMap;
    }
    
    /**
     * Связывание категорий кают с палубами через pivot таблицу
     * 
     * @param int $shipId ID теплохода
     * @param array $cabinToDeckMap Маппинг номер_каюты -> палуба из CSV
     * @return array Маппинг категория_id -> название_палубы
     */
    private function mapCategoriesToDecksFromCabins($shipId, $cabinToDeckMap)
    {
        if (empty($cabinToDeckMap)) {
            return [];
        }
        
        // Получаем номера кают из CSV
        $cabinNumbers = array_keys($cabinToDeckMap);
        $placeholders = implode(',', array_fill(0, count($cabinNumbers), '?'));
        
        // Находим категории кают через pivot таблицу для кают из CSV
        // Связь: cabins.number (физический номер каюты из CSV) -> cabins.cabin_category_id
        $stmt = $this->db->getPdo()->prepare("
            SELECT c.number as cabin_number, c.cabin_category_id
            FROM cabins c
            INNER JOIN cabin_categories cc ON c.cabin_category_id = cc.id
            WHERE c.number IN ($placeholders)
            AND cc.ship_id = ?
        ");
        
        $params = array_merge($cabinNumbers, [$shipId]);
        $stmt->execute($params);
        
        $categoryToDeckCount = []; // категория_id => [палуба => количество]
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $cabinNumber = (int)$row['cabin_number'];
            $categoryId = (int)$row['cabin_category_id'];
            
            // Получаем палубу для этой каюты из CSV
            if (isset($cabinToDeckMap[$cabinNumber])) {
                $deckName = $cabinToDeckMap[$cabinNumber];
                
                if (!isset($categoryToDeckCount[$categoryId])) {
                    $categoryToDeckCount[$categoryId] = [];
                }
                
                if (!isset($categoryToDeckCount[$categoryId][$deckName])) {
                    $categoryToDeckCount[$categoryId][$deckName] = 0;
                }
                
                $categoryToDeckCount[$categoryId][$deckName]++;
            }
        }
        
        // Для каждой категории выбираем палубу с наибольшим количеством кают
        $categoryToDeckMap = [];
        foreach ($categoryToDeckCount as $categoryId => $deckCounts) {
            // Сортируем по количеству кают (по убыванию)
            arsort($deckCounts);
            
            // Берем палубу с наибольшим количеством кают
            $deckName = key($deckCounts);
            $categoryToDeckMap[$categoryId] = $deckName;
        }
        
        return $categoryToDeckMap;
    }

    /**
     * Извлечение палуб из описаний категорий кают
     * Адаптировано из getGermesDeck из exist/Germes.php
     */
    private function extractDecksFromDescriptions()
    {
        // Получаем все категории с описаниями, у которых еще нет палубы (из CSV)
        $stmt = $this->db->getPdo()->query("
            SELECT id, description 
            FROM cabin_categories 
            WHERE description IS NOT NULL 
            AND description != ''
            AND (deck_id IS NULL OR deck_id = 0)
        ");
        
        $categoryToDeckMap = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $categoryId = (int)$row['id'];
            $description = $row['description'];
            
            // Извлекаем палубу из описания
            $deckName = $this->extractDeckNameFromDescription($description);
            
            if ($deckName) {
                // Сохраняем палубу в базу
                $deckId = $this->db->saveDeck($deckName);
                
                if ($deckId) {
                    $categoryToDeckMap[$categoryId] = $deckId;
                }
            }
        }
        
        return $categoryToDeckMap;
    }

    /**
     * Извлечение названия палубы из описания каюты
     * Использует простой поиск паттернов "название+палуб" в тексте
     * Ищет вхождения типа: нижн+палуб, главн+палуб, средн+палуб, шлюп+палуб, верхн+палуб
     * Важно: последовательность должна соблюдаться - сначала "название", потом "палуб"
     */
    private function extractDeckNameFromDescription($description)
    {
        if (empty($description)) {
            return null;
        }
        
        // Нормализуем текст (регистронезависимый поиск)
        $text = mb_strtolower($description);
        
        // Паттерны для поиска: [префикс_названия => полное_название_палубы]
        $deckPatterns = [
            'нижн' => 'Нижняя палуба',
            'главн' => 'Главная палуба',
            'средн' => 'Средняя палуба',
            'шлюп' => 'Шлюпочная палуба',
            'шлюпочн' => 'Шлюпочная палуба',
            'солнечн' => 'Солнечная палуба',
            'прогулочн' => 'Прогулочная палуба',
            'верхн' => 'Верхняя палуба',
            'багажн' => 'Багажная палуба'
        ];
        
        // Ищем каждый паттерн в тексте
        foreach ($deckPatterns as $pattern => $deckName) {
            // Ищем позицию паттерна (например, "нижн")
            $patternPos = mb_strpos($text, $pattern);
            if ($patternPos === false) {
                continue;
            }
            
            // Ищем позицию слова "палуб" после паттерна
            $deckWordPos = mb_strpos($text, 'палуб', $patternPos);
            if ($deckWordPos === false) {
                continue;
            }
            
            // Проверяем, что "палуб" идет после паттерна (последовательность соблюдена)
            if ($deckWordPos > $patternPos) {
                // Проверяем расстояние между паттерном и "палуб" (не должно быть слишком большим)
                // Ограничиваем до 50 символов, чтобы исключить ложные срабатывания
                $distance = $deckWordPos - $patternPos - mb_strlen($pattern);
                if ($distance >= 0 && $distance <= 50) {
                    return $deckName;
                }
            }
        }
        
        // Также проверяем существующие палубы из SQLite (для динамически созданных)
        $existingDecks = $this->getExistingDecks();
        foreach ($existingDecks as $deckNameLower => $deckNameNormalized) {
            // Берем первые 4-6 символов названия палубы как паттерн
            $deckPrefix = mb_substr($deckNameLower, 0, 6);
            // Убираем слово "палуба" из префикса, если оно есть
            $deckPrefix = preg_replace('/\s*палуб.*$/', '', $deckPrefix);
            $deckPrefix = trim($deckPrefix);
            
            if (mb_strlen($deckPrefix) >= 4) {
                $patternPos = mb_strpos($text, $deckPrefix);
                if ($patternPos !== false) {
                    $deckWordPos = mb_strpos($text, 'палуб', $patternPos);
                    if ($deckWordPos !== false && $deckWordPos > $patternPos) {
                        $distance = $deckWordPos - $patternPos - mb_strlen($deckPrefix);
                        if ($distance >= 0 && $distance <= 50) {
                            return $deckNameNormalized;
                        }
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Извлечение палубы из многострочного формата
     * Обрабатывает случаи типа:
     * "Главная палуба 203-236 — 9,02 м2
     *  Средняя палуба 301-314 — 9,02 м2
     *  Шлюпочная палуба 403-430 — 9,87 м2"
     * 
     * Возвращает первую найденную палубу
     */
    private function extractDeckFromMultiLineFormat($description)
    {
        // Разбиваем на строки
        $lines = preg_split('/[\r\n]+/', $description);
        
        // Получаем все существующие палубы из SQLite
        $existingDecks = $this->getExistingDecks();
        
        // Известные паттерны палуб
        $knownDeckPatterns = [
            'нижн' => 'Нижняя палуба',
            'главн' => 'Главная палуба',
            'средн' => 'Средняя палуба',
            'шлюпочн' => 'Шлюпочная палуба',
            'солнечн' => 'Солнечная палуба',
            'прогулочн' => 'Прогулочная палуба',
            'верхн' => 'Верхняя палуба',
            'багажн' => 'Багажная палуба'
        ];
        
        foreach ($lines as $line) {
            $lineLower = mb_strtolower(trim($line));
            
            // Ищем паттерн: "название палуба номер-номер"
            // Например: "главная палуба 203-236"
            foreach ($knownDeckPatterns as $pattern => $deckName) {
                // Проверяем, содержит ли строка паттерн и слово "палуба"
                if (mb_strpos($lineLower, $pattern) !== false && mb_strpos($lineLower, 'палуб') !== false) {
                    // Проверяем, что это не просто упоминание типа "выход на палубу"
                    // Ищем паттерн "название палуба" в начале строки или после пробела
                    $patternPos = mb_strpos($lineLower, $pattern);
                    $deckWordPos = mb_strpos($lineLower, 'палуб');
                    
                    // Паттерн должен быть перед словом "палуба"
                    if ($patternPos !== false && $deckWordPos !== false && $patternPos < $deckWordPos) {
                        // Проверяем расстояние между паттерном и "палуба" (не более 2 слов)
                        $between = mb_substr($lineLower, $patternPos + mb_strlen($pattern), $deckWordPos - $patternPos - mb_strlen($pattern));
                        $betweenWords = preg_split('/\s+/', trim($between));
                        
                        if (count($betweenWords) <= 2) {
                            return $deckName;
                        }
                    }
                }
            }
            
            // Также проверяем существующие палубы
            foreach ($existingDecks as $deckNameLower => $deckNameNormalized) {
                // Берем первые 4 символа названия палубы
                $deckPrefix = mb_substr($deckNameLower, 0, 4);
                if (mb_strlen($deckPrefix) >= 4 && mb_strpos($lineLower, $deckPrefix) !== false && mb_strpos($lineLower, 'палуб') !== false) {
                    $prefixPos = mb_strpos($lineLower, $deckPrefix);
                    $deckWordPos = mb_strpos($lineLower, 'палуб');
                    
                    if ($prefixPos !== false && $deckWordPos !== false && $prefixPos < $deckWordPos) {
                        $between = mb_substr($lineLower, $prefixPos + mb_strlen($deckPrefix), $deckWordPos - $prefixPos - mb_strlen($deckPrefix));
                        $betweenWords = preg_split('/\s+/', trim($between));
                        
                        if (count($betweenWords) <= 2) {
                            return $deckNameNormalized;
                        }
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Получение списка существующих палуб из SQLite
     * Возвращает массив [lowercase_name => normalized_name]
     */
    private function getExistingDecks()
    {
        static $decksCache = null;
        
        if ($decksCache === null) {
            $stmt = $this->db->getPdo()->query("SELECT name FROM decks");
            $decksCache = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $normalizedName = $row['name'];
                $lowerName = mb_strtolower($normalizedName);
                $decksCache[$lowerName] = $normalizedName;
            }
        }
        
        return $decksCache;
    }

    /**
     * Проверка, является ли слово названием палубы
     * Адаптировано из isDecsName старой реализации
     * Использует сравнение по первым 4 символам с существующими палубами
     */
    private function isDeckNameByPrefix($word, $existingDecks)
    {
        if (mb_strlen($word) < 4) {
            return false;
        }
        
        $wordLower = mb_strtolower(trim($word));
        // Убираем знаки препинания в конце слова
        $wordLower = preg_replace('/[.,;:!?\)]+$/', '', $wordLower);
        
        // Берем первые 4 символа (как в старой реализации)
        $wordPrefix = mb_substr($wordLower, 0, 4);
        
        // Ищем совпадение в существующих палубах
        // В старой реализации: strpos($deck_name, $word) !== false
        // Т.е. проверяем, содержит ли название палубы первые 4 символа слова
        foreach ($existingDecks as $deckNameLower => $deckNameNormalized) {
            if (mb_strpos($deckNameLower, $wordPrefix) !== false) {
                // Возвращаем нормализованное название палубы
                return $deckNameNormalized;
            }
        }
        
        // Если не нашли в существующих, проверяем известные паттерны
        // Это нужно для первого прохода, когда палубы еще не созданы
        $knownDeckPatterns = [
            'нижн' => 'Нижняя палуба',
            'главн' => 'Главная палуба',
            'средн' => 'Средняя палуба',
            'шлюпочн' => 'Шлюпочная палуба',
            'солнечн' => 'Солнечная палуба',
            'прогулочн' => 'Прогулочная палуба',
            'верхн' => 'Верхняя палуба',
            'багажн' => 'Багажная палуба'
        ];
        
        foreach ($knownDeckPatterns as $pattern => $deckName) {
            // Проверяем, начинается ли слово с паттерна или паттерн содержит префикс слова
            if (mb_strpos($wordLower, $pattern) === 0 || mb_strpos($pattern, $wordPrefix) === 0) {
                return $deckName;
            }
        }
        
        return false;
    }

    /**
     * Проверка, является ли слово "палуба" или производным
     */
    private function isDeckWord($word)
    {
        if (empty($word)) {
            return false;
        }
        
        $wordLower = mb_strtolower($word);
        return mb_substr($wordLower, 0, 5) == 'палуб';
    }

    /**
     * Нормализация названия палубы (первая буква заглавная)
     */
    private function normalizeDeckName($deckName)
    {
        if (empty($deckName)) {
            return null;
        }
        
        // Преобразуем в нормальное название с заглавной буквы
        $normalized = mb_strtoupper(mb_substr($deckName, 0, 1)) . mb_substr($deckName, 1);
        
        // Добавляем "палуба" если нужно
        if (mb_strpos($normalized, 'палуб') === false) {
            $normalized .= ' палуба';
        }
        
        return $normalized;
    }

    /**
     * Получение класса каюты через pivot таблицу
     */
    private function getGermesCabinClass($priceId, $cabins, $pivot)
    {
        if (!$pivot || !isset($pivot['Kauta'])) {
            return false;
        }
        
        $pivotItems = $pivot['Kauta'];
        if (isset($pivotItems['@attributes'])) {
            $pivotItems = [$pivotItems];
        }
        
        $idClassKauta = false;
        foreach ($pivotItems as $pivotItem) {
            $id = $pivotItem['@attributes']['id'] ?? null;
            if ($priceId == $id) {
                $idClassKauta = $pivotItem['@attributes']['idClassKauta'] ?? null;
                break;
            }
        }
        
        if (!$idClassKauta) {
            return false;
        }
        
        if (!$cabins || !isset($cabins['Класс'])) {
            return false;
        }
        
        $cabinClasses = $cabins['Класс'];
        if (isset($cabinClasses['@attributes'])) {
            $cabinClasses = [$cabinClasses];
        }
        
        foreach ($cabinClasses as $cabinClass) {
            $id = $cabinClass['@attributes']['id'] ?? null;
            if ($idClassKauta == $id) {
                return $cabinClass;
            }
        }
        
        return false;
    }
}

