<?php namespace Zen\Worker\Console\volga;

use Mcmraak\Rivercrs\Classes\Getter;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class VolgaDataProcessor
{
    private $db;
    private $getter;
    private $timeout;
    private $limit;

    public function __construct($database, $timeout = 30, $limit = null)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->db = $database;
        $this->getter = new Getter();
        $this->timeout = $timeout;
        $this->limit = $limit;
    }

    /**
     * Обработка всех данных из dump
     */
    public function processAllData($dump)
    {
        ProcessLog::add("Начинаем обработку данных Volga...");
        
        // Обрабатываем данные в правильном порядке
        $this->processShipsData($dump);
        $this->processDecksData($dump);
        $this->processCabinCategoriesData($dump);
        $this->processCabinsData($dump);
        $this->processCruisesData($dump);
        $this->processPricesData($dump);
        
        ProcessLog::add("Обработка данных Volga завершена");
    }

    /**
     * Обработка данных о теплоходах
     */
    public function processShipsData($dump)
    {
        if (!isset($dump['ships']['ship'])) {
            ProcessLog::add("Нет данных о теплоходах");
            return;
        }

        $ships = [];
        $items = $dump['ships']['ship'];
        
        // Обрабатываем случай, когда ship один элемент
        if (isset($items['@attributes'])) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $data = $item['@attributes'] ?? $item;
            $ships[] = [
                'id' => (int)$data['id'],
                'name' => $data['name']
            ];
        }

        if (!empty($ships)) {
            $this->db->saveShipsBatch($ships);
            ProcessLog::add("Сохранено теплоходов: " . count($ships));
        }
    }

    /**
     * Обработка данных о палубах
     */
    public function processDecksData($dump)
    {
        if (!isset($dump['decks']['deck'])) {
            ProcessLog::add("Нет данных о палубах");
            return;
        }

        $decks = [];
        $items = $dump['decks']['deck'];
        
        // Обрабатываем случай, когда deck один элемент
        if (isset($items['@attributes'])) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $data = $item['@attributes'] ?? $item;
            // В Volga палубы могут быть связаны с теплоходами через каюты
            // Пока сохраняем без ship_id, потом обновим через каюты
            $decks[] = [
                'id' => (int)$data['id'],
                'name' => $data['name'],
                'ship_id' => null // Будет обновлено через каюты
            ];
        }

        if (!empty($decks)) {
            $this->db->saveDecksBatch($decks);
            ProcessLog::add("Сохранено палуб: " . count($decks));
        }
    }

    /**
     * Обработка данных о категориях кают
     */
    public function processCabinCategoriesData($dump)
    {
        if (!isset($dump['classes']['class'])) {
            ProcessLog::add("Нет данных о категориях кают");
            return;
        }

        $categories = [];
        $items = $dump['classes']['class'];
        
        // Обрабатываем случай, когда class один элемент
        if (isset($items['@attributes'])) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $data = $item['@attributes'] ?? $item;
            $categories[] = [
                'id' => (int)$data['id'],
                'name' => $data['name'] ?? '',
                'comment' => $data['comment'] ?? null,
                'places_main_count' => (int)($data['m_count'] ?? 0),
                'places_extra_count' => (int)($data['r_count'] ?? 0),
                'deck_id' => null, // Будет обновлено через каюты
                'ship_id' => null, // Будет обновлено через каюты
                'no_full' => (int)($data['no_full'] ?? 0)
            ];
        }

        if (!empty($categories)) {
            $this->db->saveCabinCategoriesBatch($categories);
            ProcessLog::add("Сохранено категорий кают: " . count($categories));
        }
    }

    /**
     * Обработка данных о каютах (связь class_id и deck_id)
     */
    public function processCabinsData($dump)
    {
        if (!isset($dump['cabins']['cabin'])) {
            ProcessLog::add("Нет данных о каютах");
            return;
        }

        $cabins = [];
        $items = $dump['cabins']['cabin'];
        
        // Обрабатываем случай, когда cabin один элемент
        if (isset($items['@attributes'])) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $data = $item['@attributes'] ?? $item;
            $cabins[] = [
                'id' => (int)$data['id'],
                'class_id' => (int)($data['class_id'] ?? 0),
                'deck_id' => (int)($data['deck'] ?? 0),
                'ship_id' => null // Будет обновлено через круизы
            ];
        }

        if (!empty($cabins)) {
            $this->db->saveCabinsBatch($cabins);
            ProcessLog::add("Сохранено кают: " . count($cabins));
        }
    }

    /**
     * Обработка данных о круизах
     */
    public function processCruisesData($dump)
    {
        if (!isset($dump['cruises']['cruise'])) {
            ProcessLog::add("Нет данных о круизах");
            return;
        }

        $cruises = [];
        $waybills = [];
        $shipCabinMapping = []; // Для обновления ship_id в каютах
        $items = $dump['cruises']['cruise'];
        
        // Обрабатываем случай, когда cruise один элемент
        if (isset($items['@attributes'])) {
            $items = [$items];
        }

        // Применяем лимит если указан
        if ($this->limit) {
            $items = array_slice($items, 0, $this->limit);
            ProcessLog::add("⚠️  Ограничение парсинга: обрабатываем только {$this->limit} круизов");
        }

        $processed = 0;
        foreach ($items as $item) {
            $data = $item['@attributes'] ?? $item;
            $cruiseId = (int)$data['id'];
            
            if (!$cruiseId) {
                continue;
            }

            $shipId = (int)($data['ship_id'] ?? 0);
            
            // Собираем информацию о каютах для этого круиза (если есть в XML)
            if (isset($item['cabin']) && is_array($item['cabin'])) {
                $cabins = isset($item['cabin']['@attributes']) ? [$item['cabin']] : $item['cabin'];
                foreach ($cabins as $cabinItem) {
                    $cabinData = $cabinItem['@attributes'] ?? $cabinItem;
                    $cabinId = (int)($cabinData['id'] ?? 0);
                    if ($cabinId && $shipId) {
                        $shipCabinMapping[$cabinId] = $shipId;
                    }
                }
            }

            // Формируем даты
            $beginDate = $data['begin_date'] ?? null;
            $beginTime = $data['begin_time'] ?? null;
            $endDate = $data['end_date'] ?? null;
            $endTime = $data['end_time'] ?? null;
            
            $dateStart = null;
            $dateEnd = null;
            
            if ($beginDate && $beginTime) {
                $dateStart = date('Y-m-d', strtotime($beginDate)) . ' ' . $beginTime;
            }
            
            if ($endDate && $endTime) {
                $dateEnd = date('Y-m-d', strtotime($endDate)) . ' ' . $endTime;
            }

            // Формируем путевой лист
            $waybill = null;
            $waybillData = null;
            
            if (!empty($data['route'])) {
                try {
                    $waybill = $this->volgaWay($data);
                    if (is_array($waybill) && count($waybill) >= 2) {
                        $waybillData = json_encode($waybill);
                        
                        // Сохраняем путевой лист для batch сохранения
                        foreach ($waybill as $index => $point) {
                            $waybills[] = [
                                'cruise_id' => $cruiseId,
                                'town_name' => $point['town_name'] ?? '',
                                'town_id' => $point['town'] ?? 0,
                                'order_index' => $index,
                                'bold' => $point['bold'] ?? 0,
                                'excursion' => $point['excursion'] ?? ''
                            ];
                        }
                    }
                } catch (Exception $e) {
                    ProcessLog::add("Ошибка формирования путевого листа для круиза $cruiseId: " . $e->getMessage());
                }
            }

            $cruises[] = [
                'volga_cruise_id' => $cruiseId,
                'volga_ship_id' => $shipId,
                'name' => $data['name'] ?? null,
                'route' => $data['route'] ?? null,
                'begin_date' => $beginDate,
                'begin_time' => $beginTime,
                'end_date' => $endDate,
                'end_time' => $endTime,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'waybill_data' => $waybillData
            ];
            
            $processed++;
        }

        if (!empty($cruises)) {
            $this->db->saveCruisesBatch($cruises);
            ProcessLog::add("Сохранено круизов: " . count($cruises));
        }

        if (!empty($waybills)) {
            $this->db->saveWaybillsBatch($waybills);
            ProcessLog::add("Сохранено путевых листов: " . count($waybills));
        }

        // Обновляем ship_id для кают на основе круизов
        if (!empty($shipCabinMapping)) {
            $this->updateCabinsShipId($shipCabinMapping);
        }
        
        // Обновляем ship_id для кают на основе круизов через цены
        $this->updateCabinsShipIdFromCruises();
        
        // Обновляем ship_id для палуб на основе кают
        $this->updateDecksShipId();
        
        // Обновляем ship_id и deck_id для категорий кают на основе кают
        $this->updateCabinCategoriesRelations();
    }

    /**
     * Обработка данных о ценах
     */
    public function processPricesData($dump)
    {
        if (!isset($dump['prices']['price'])) {
            ProcessLog::add("Нет данных о ценах");
            return;
        }

        $prices = [];
        $items = $dump['prices']['price'];
        
        // Обрабатываем случай, когда price один элемент
        if (isset($items['@attributes'])) {
            $items = [$items];
        }

        // Получаем SPO данные
        $spos = [];
        if (isset($dump['spos']['spo'])) {
            $spoItems = $dump['spos']['spo'];
            if (isset($spoItems['@attributes'])) {
                $spoItems = [$spoItems];
            }
            
            foreach ($spoItems as $spoItem) {
                $spoData = $spoItem['@attributes'] ?? $spoItem;
                $cruiseId = (int)($spoData['cruise_id'] ?? 0);
                $classId = (int)($spoData['class_id'] ?? 0);
                if ($cruiseId && $classId) {
                    $spos["$cruiseId:$classId"] = (int)($spoData['spo'] ?? 0);
                }
            }
        }

        foreach ($items as $item) {
            $data = $item['@attributes'] ?? $item;
            $cruiseId = (int)($data['cruise_id'] ?? 0);
            $classId = (int)($data['class_id'] ?? 0);
            $nofull = (int)($data['nofull'] ?? 0);
            
            if (!$cruiseId || !$classId) {
                continue;
            }

            // Пропускаем цены с nofull=1 (как в оригинале)
            if ($nofull == 1) {
                continue;
            }

            $priceValue = (int)($data['price'] ?? 0);
            if ($priceValue <= 0) {
                continue;
            }

            // Получаем SPO для этой цены
            $price2Value = $spos["$cruiseId:$classId"] ?? null;

            $prices[] = [
                'cruise_id' => $cruiseId,
                'cabin_category_id' => $classId,
                'price_value' => $priceValue,
                'price2_value' => $price2Value,
                'nofull' => $nofull
            ];
        }

        if (!empty($prices)) {
            $this->db->savePricesBatch($prices);
            ProcessLog::add("Сохранено цен: " . count($prices));
        }
    }

    /**
     * Обновление ship_id для кают на основе круизов
     */
    private function updateCabinsShipId($shipCabinMapping)
    {
        $pdo = $this->db->getPdo();
        $updated = 0;
        
        foreach ($shipCabinMapping as $cabinId => $shipId) {
            $stmt = $pdo->prepare("UPDATE cabins SET ship_id = ? WHERE id = ? AND (ship_id IS NULL OR ship_id = 0)");
            if ($stmt->execute([$shipId, $cabinId])) {
                $updated++;
            }
        }
        
        if ($updated > 0) {
            ProcessLog::add("Обновлено ship_id для кают: $updated");
        }
    }

    /**
     * Обновление ship_id для кают на основе круизов через цены
     */
    private function updateCabinsShipIdFromCruises()
    {
        $pdo = $this->db->getPdo();
        
        // Обновляем ship_id для кают на основе круизов через цены и категории кают
        $stmt = $pdo->prepare("
            UPDATE cabins 
            SET ship_id = (
                SELECT cr.ship_id 
                FROM cruises cr
                JOIN prices p ON p.cruise_id = cr.id
                WHERE p.cabin_category_id = cabins.class_id
                AND cr.ship_id IS NOT NULL
                LIMIT 1
            )
            WHERE (ship_id IS NULL OR ship_id = 0)
            AND EXISTS (
                SELECT 1 
                FROM prices p
                JOIN cruises cr ON cr.id = p.cruise_id
                WHERE p.cabin_category_id = cabins.class_id
                AND cr.ship_id IS NOT NULL
            )
        ");
        
        $stmt->execute();
        $updated = $stmt->rowCount();
        
        if ($updated > 0) {
            ProcessLog::add("Обновлено ship_id для кают из круизов: $updated");
        }
    }

    /**
     * Обновление ship_id для палуб на основе кают
     */
    private function updateDecksShipId()
    {
        $pdo = $this->db->getPdo();
        
        // Обновляем ship_id для палуб на основе кают
        $stmt = $pdo->prepare("
            UPDATE decks 
            SET ship_id = (
                SELECT c.ship_id 
                FROM cabins c 
                WHERE c.deck_id = decks.id 
                AND c.ship_id IS NOT NULL 
                LIMIT 1
            )
            WHERE (ship_id IS NULL OR ship_id = 0)
            AND EXISTS (
                SELECT 1 
                FROM cabins c 
                WHERE c.deck_id = decks.id 
                AND c.ship_id IS NOT NULL
            )
        ");
        
        $stmt->execute();
        $updated = $stmt->rowCount();
        
        if ($updated > 0) {
            ProcessLog::add("Обновлено ship_id для палуб: $updated");
        }
    }

    /**
     * Обновление ship_id и deck_id для категорий кают на основе кают
     */
    private function updateCabinCategoriesRelations()
    {
        $pdo = $this->db->getPdo();
        
        // Обновляем ship_id и deck_id для категорий кают на основе кают
        $stmt = $pdo->prepare("
            UPDATE cabin_categories 
            SET ship_id = (
                SELECT c.ship_id 
                FROM cabins c 
                WHERE c.class_id = cabin_categories.id 
                AND c.ship_id IS NOT NULL 
                LIMIT 1
            ),
            deck_id = (
                SELECT c.deck_id 
                FROM cabins c 
                WHERE c.class_id = cabin_categories.id 
                AND c.deck_id IS NOT NULL 
                LIMIT 1
            )
            WHERE (ship_id IS NULL OR deck_id IS NULL)
        ");
        
        $stmt->execute();
        $updated = $stmt->rowCount();
        
        if ($updated > 0) {
            ProcessLog::add("Обновлено связей для категорий кают: $updated");
        }
    }

    /**
     * Формирование путевого листа Volga (аналог метода volgaWay из Volga.php)
     */
    private function volgaWay($volgaCruise)
    {
        $waybillString = $volgaCruise['route'] ?? '';
        
        if (empty($waybillString)) {
            return [];
        }

        $way = $this->getter->checkSeparator($waybillString);
        $way = explode(' - ', $way);
        $waybill = [];
        
        foreach ($way as $route) {
            $route = trim($route);
            if (empty($route)) {
                continue;
            }
            
            // Очищаем название города от мусора перед сохранением
            $cleanTownName = $this->cleanTownName($route);
            
            $townId = $this->getter->getTownId($route, 'volga');
            $waybill[] = [
                'town' => $townId,
                'town_name' => $cleanTownName, // Сохраняем очищенное название
                'excursion' => '',
                'bold' => 0,
            ];
        }
        
        return $waybill;
    }

    /**
     * Очистка названия города от мусора (скобки, кавычки, дополнительные описания)
     * 
     * Источник Volga предоставляет маршруты в поле route, где могут быть описания в скобках и кавычках.
     * Примеры из реальных данных:
     * - "Пермь - Вытегра + «Онежское кольцо» (с ночёвкой на базе отдыха) – Петрозаводск - Пермь"
     * - "Пермь - «Русский Север» (Череповец + Вологда, Сизьма) - Весьегонск (р.Молога) - Пермь"
     * 
     * Метод извлекает чистое название города, убирая описания.
     * Примеры:
     * "«Онежское кольцо» ⏴с ночёвкой на базе отдыха⏵" → "Онежское кольцо"
     * "Пенза, Лермонтово, Белинский ⏴2 дня⏵" → "Пенза, Лермонтово, Белинский"
     * "Весьегонск (р.Молога)" → "Весьегонск"
     */
    private function cleanTownName($route)
    {
        // Заменяем специальные символы Volga на обычные (как в getTownId)
        $clean = str_replace('⏹', '-', $route);
        $clean = str_replace('⏴', '(', $clean);
        $clean = str_replace('⏵', ')', $clean);
        
        // Убираем текст в скобках (включая сами скобки)
        // Это уберёт описания типа "(с ночёвкой на базе отдыха)", "(р.Молога)", "(1 день)" и т.д.
        $clean = preg_replace('/\([^)]*\)/u', '', $clean);
        
        // Убираем кавычки разных типов (русские и английские)
        $clean = str_replace(['«', '»', '"', '"', "'", "'"], '', $clean);
        
        // Убираем лишние пробелы и знаки + (которые используются для объединения городов)
        $clean = preg_replace('/\s*\+\s*/u', ' ', $clean);
        $clean = preg_replace('/ {2,}/u', ' ', $clean);
        $clean = trim($clean);
        
        return $clean;
    }
}

