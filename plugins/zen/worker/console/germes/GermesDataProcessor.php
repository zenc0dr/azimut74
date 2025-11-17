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
            
            $categories[] = [
                'id' => (int)$data['id'],
                'name' => $data['Название'] ?? '',
                'description' => $description,
                'ship_id' => null // Связь с теплоходом устанавливается позже
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
            $cabins[] = [
                'id' => (int)$data['id'],
                'cabin_category_id' => (int)$data['idClassKauta']
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
        
        // Обновляем ship_id в cabin_categories на основе данных из круизов и цен
        ProcessLog::add('Обновление связей категорий кают с теплоходами...');
        $updated = $this->db->updateCabinCategoriesShipId();
        ProcessLog::add("Обновлено связей категорий кают с теплоходами: $updated");
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

