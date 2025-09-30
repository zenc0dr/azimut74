<?php namespace Zen\Worker\Console\gama;

use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Mcmraak\Rivercrs\Models\Checkins;
use Mcmraak\Rivercrs\Models\Cabins;
use Mcmraak\Rivercrs\Models\Pricing;
use Mcmraak\Rivercrs\Models\Waybills;
use Mcmraak\Rivercrs\Models\Decks;
use Mcmraak\Rivercrs\Models\Towns;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Classes\Getter;
use Carbon\Carbon;
use DB;
use Exception;
use Zen\Worker\Classes\ProcessLog;
use Zen\Worker\Pools\RiverCrs;

/**
 * Класс для импорта данных из SQLite в основную базу данных
 * Обрабатывает заезды последовательно с полной валидацией каждого
 */
class GamaMainDbImporter extends RiverCrs
{
    private $sqliteDb;
    private $getter;
    private $importedShips = [];
    private $importedCabins = [];
    private $limit = null;
    private $stats = [
        'ships' => 0,
        'cabins' => 0,
        'cruises' => 0,
        'prices' => 0,
        'waybills' => 0,
        'errors' => 0
    ];

    public function __construct(GamaDatabase $sqliteDb)
    {
        $this->sqliteDb = $sqliteDb;
        $this->getter = new Getter();
    }

    /**
     * Установка лимита для тестирования
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * Полный импорт всех данных (последовательно по заездам)
     */
    public function importAll()
    {
        ProcessLog::add("🚀 Начинаем импорт данных из SQLite в основную БД...");
        
        // Получаем все круизы из SQLite
        $cruises = $this->sqliteDb->getAllCruises();
        
        if ($this->limit) {
            $cruises = array_slice($cruises, 0, $this->limit);
            ProcessLog::add("⚠️  Ограничение: импортируем только {$this->limit} заездов");
        }
        
        $totalCruises = count($cruises);
        ProcessLog::add("📊 Всего заездов для импорта: $totalCruises");
        
        $processed = 0;
        $errors = 0;
        
        foreach ($cruises as $cruiseData) {
            $processed++;
            ProcessLog::add("🔄 Обработка заезда $processed из $totalCruises: {$cruiseData['name']} (ID: {$cruiseData['id']})");
            
            try {
                // Обрабатываем заезд последовательно
                $this->processCruiseSequentially($cruiseData);
                
                ProcessLog::add("✅ Заезд $processed успешно обработан");
                
            } catch (Exception $e) {
                $errors++;
                $this->stats['errors']++;
                
                ProcessLog::add("❌ КРИТИЧЕСКАЯ ОШИБКА в заезде $processed: " . $e->getMessage());
                ProcessLog::add("❌ Остановка импорта. Обработано заездов: " . ($processed - 1) . " из $totalCruises");
                
                // Останавливаем выполнение при ошибке
                throw new Exception("Ошибка обработки заезда $processed: " . $e->getMessage());
            }
        }
        
        ProcessLog::add("✅ Импорт завершен. Статистика: " . json_encode($this->stats));
        return $this->stats;
    }

    /**
     * Последовательная обработка одного заезда
     */
    private function processCruiseSequentially($cruiseData)
    {
        $gamaShipId = $cruiseData['gama_ship_id'];
        $gamaCruiseId = $cruiseData['id'];
        
        // 1. Обрабатываем теплоход
        $ship = $this->processShip($gamaShipId, $cruiseData['ship_name']);
        if (!$ship) {
            throw new Exception("Теплоход не найден или исключен: {$cruiseData['ship_name']}");
        }
        
        // 2. Обрабатываем категории кают для этого теплохода
        $this->processCabinCategories($gamaShipId);
        
        // 3. Получаем цены для заезда
        $prices = $this->sqliteDb->getPricesByCruiseId($gamaCruiseId);
        if (empty($prices)) {
            throw new Exception("Нет валидных цен для заезда $gamaCruiseId");
        }
        
        ProcessLog::add("💰 Найдено цен для заезда $gamaCruiseId: " . count($prices));
        
        // 4. Создаем/обновляем заезд
        $checkin = $this->createOrUpdateCheckin($cruiseData, $ship->id);
        
        // 5. Импортируем цены
        $this->importPricesForCruise($checkin->id, $gamaCruiseId, $prices);
        
        // 6. Импортируем путевой лист
        $this->importWaybill($checkin->id, $gamaCruiseId);
        
        // 7. Валидируем результат
        $this->validateCruiseImport($checkin->id, $gamaCruiseId);
        
        $this->stats['cruises']++;
    }

    /**
     * Обработка теплохода
     */
    private function processShip($gamaShipId, $shipName)
    {
        // Проверяем, не в черном списке ли теплоход
        if (CacheSettings::shipIsBad($shipName, 'gama')) {
            ProcessLog::add("⚠️  Теплоход '$shipName' исключен из импорта (черный список)");
            return null;
        }

        // Ищем существующий теплоход по gama_id
        $ship = Ship::where('gama_id', $gamaShipId)->first();
        
        if (!$ship) {
            // Ищем по названию
            $ship = Ship::where('name', 'like', "%$shipName%")->first();
        }

        if (!$ship) {
            // Создаем новый теплоход
            $ship = new Ship();
            $ship->name = $shipName;
            $ship->desc = '';
            $ship->add_a = '';
            $ship->add_b = '';
            $ship->booking_discounts = '';
            $ship->social_discounts = '';
            $ship->youtube = '';
            $ship->banner = '';
            $ship->techs = [];
        }

        // Обновляем gama_id
        $ship->gama_id = $gamaShipId;
        $ship->save();

        if (!isset($this->importedShips[$gamaShipId])) {
            $this->importedShips[$gamaShipId] = $ship->id;
            $this->stats['ships']++;
            ProcessLog::add("✅ Теплоход '$shipName' обработан (ID: {$ship->id})");
        }

        return $ship;
    }

    /**
     * Обработка категорий кают для теплохода
     */
    private function processCabinCategories($gamaShipId)
    {
        if (isset($this->importedCabins[$gamaShipId])) {
            return; // Уже обработаны
        }

        $cabinCategories = $this->sqliteDb->getAllCabinCategories();
        $shipCabins = array_filter($cabinCategories, function($cat) use ($gamaShipId) {
            return $cat['ship_id'] == $gamaShipId;
        });

        foreach ($shipCabins as $categoryData) {
            $categoryName = $categoryData['name'];
            $places = $categoryData['places'];
            
            // Проверяем, не в черном списке ли категория кают
            if ($this->isCabinCategoryExcluded($categoryName, $this->importedShips[$gamaShipId])) {
                ProcessLog::add("⚠️  Категория кают '$categoryName' исключена из импорта");
                continue;
            }

            // Ищем существующую категорию кают
            $cabin = Cabins::where('motorship_id', $this->importedShips[$gamaShipId])
                ->where('gama_name', $categoryName)
                ->first();

            if (!$cabin) {
                // Создаем новую категорию кают
                $cabin = new Cabins();
                $cabin->motorship_id = $this->importedShips[$gamaShipId];
                $cabin->category = $categoryName;
                $cabin->gama_name = $categoryName;
                $cabin->places_main_count = $places;
                $cabin->desc = '';
                $cabin->waterway_name = '';
                $cabin->volga_name = '';
                $cabin->germes_name = '';
                $cabin->order = 0;
                $cabin->save();
            }

            $this->importedCabins[$categoryData['id']] = $cabin->id;
            $this->stats['cabins']++;
        }

        $this->importedCabins[$gamaShipId] = true; // Помечаем как обработанные
    }

    /**
     * Создание или обновление заезда
     */
    private function createOrUpdateCheckin($cruiseData, $shipId)
    {
        $gamaCruiseId = $cruiseData['id'];
        
        // Ищем существующий заезд
        $checkin = Checkins::where('eds_code', 'gama')
            ->where('eds_id', $gamaCruiseId)
            ->first();

        if (!$checkin) {
            $checkin = new Checkins();
        }

        // Обновляем данные заезда
        $checkin->date = Carbon::parse($cruiseData['date_start'])->toDateTimeString();
        $checkin->dateb = Carbon::parse($cruiseData['date_end'])->toDateTimeString();
        $checkin->desc_1 = $cruiseData['schedule_html'] ?? '';
        $checkin->motorship_id = $shipId;
        $checkin->active = 1;
        $checkin->eds_code = 'gama';
        $checkin->eds_id = (int) $gamaCruiseId;
        $checkin->waybill_id = $this->processWaybillData($cruiseData['waybill_data']);
        $checkin->save();

        // Вызываем fixCheckin для синхронизации данных
        $this->fixCheckin($checkin->id);

        ProcessLog::add("✅ Заезд создан/обновлен (ID: {$checkin->id})");
        
        return $checkin;
    }

    /**
     * Импорт цен для заезда
     */
    private function importPricesForCruise($checkinId, $gamaCruiseId, $prices)
    {
        ProcessLog::add("💰 Импорт цен для заезда $gamaCruiseId (checkin_id: $checkinId)...");
        
        // Подготавливаем данные для массовой вставки
        $insertPrices = [];
        $validPricesCount = 0;
        
        foreach ($prices as $priceData) {
            $cabinCategoryId = $priceData['cabin_category_id'];
            
            // Проверяем, что категория кают была импортирована
            if (!isset($this->importedCabins[$cabinCategoryId])) {
                ProcessLog::add("⚠️  Категория кают $cabinCategoryId не импортирована, пропускаем цену");
                continue;
            }
            
            $cabinId = $this->importedCabins[$cabinCategoryId];
            
            $insertPrices[] = [
                'checkin_id' => $checkinId,
                'cabin_id' => $cabinId,
                'price_a' => $priceData['price_a'],
                'price_b' => $priceData['price_b'] ?? null,
                'desc' => null
            ];
            $validPricesCount++;
        }
        
        // Если нет валидных цен, выбрасываем исключение
        if (empty($insertPrices)) {
            throw new Exception("Нет валидных цен для заезда $gamaCruiseId");
        }
        
        // Удаляем старые цены и вставляем новые
        DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $checkinId)
            ->delete();
        
        DB::table('mcmraak_rivercrs_pricing')
            ->insert($insertPrices);
        
        $this->stats['prices'] += $validPricesCount;
        
        // Валидация с проверкой
        $insertedCount = DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $checkinId)
            ->count();
        
        if ($insertedCount === 0) {
            throw new Exception("Цены не были добавлены в базу данных!");
        }
        
        ProcessLog::add("✅ Цены для заезда $gamaCruiseId: добавлено $insertedCount из $validPricesCount");
    }

    /**
     * Импорт путевого листа
     */
    private function importWaybill($checkinId, $gamaCruiseId)
    {
        $waybills = $this->sqliteDb->getCruiseWaybill($gamaCruiseId);
        
        if (empty($waybills)) {
            ProcessLog::add("⚠️  Путевой лист для заезда $gamaCruiseId не найден");
            return;
        }

        // Удаляем существующие путевые листы для этого заезда
        Waybills::where('checkin_id', $checkinId)->delete();

        foreach ($waybills as $waybillData) {
            $waybill = new Waybills();
            $waybill->checkin_id = $checkinId;
            $waybill->town_id = $this->getOrCreateTown($waybillData['town_name']);
            $waybill->order = (int) ($waybillData['order_index'] ?? 0);
            $waybill->excursion = '';
            $waybill->bold = (int) ($waybillData['is_bold'] ?? 0);
            $waybill->save();

            $this->stats['waybills']++;
        }

        ProcessLog::add("✅ Путевой лист для заезда $gamaCruiseId импортирован");
    }

    /**
     * Валидация импорта заезда
     */
    private function validateCruiseImport($checkinId, $gamaCruiseId)
    {
        // Проверяем, что заезд существует
        $checkin = Checkins::find($checkinId);
        if (!$checkin) {
            throw new Exception("Заезд $checkinId не найден после импорта");
        }

        // Проверяем, что есть цены
        $pricesCount = DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $checkinId)
            ->count();
        
        if ($pricesCount === 0) {
            throw new Exception("У заезда $checkinId нет цен после импорта");
        }

        // Проверяем, что есть путевой лист
        $waybillsCount = Waybills::where('checkin_id', $checkinId)->count();
        
        if ($waybillsCount === 0) {
            ProcessLog::add("⚠️  У заезда $checkinId нет путевого листа");
        }

        ProcessLog::add("✅ Валидация заезда $gamaCruiseId пройдена (цены: $pricesCount, маршрут: $waybillsCount)");
    }

    /**
     * Проверка исключения категории кают
     */
    private function isCabinCategoryExcluded($categoryName, $shipId)
    {
        $getter = new Getter();
        return $getter->isCabinNotLet($categoryName, $shipId);
    }

    /**
     * Обработка данных путевого листа
     */
    private function processWaybillData($waybillData)
    {
        if (!$waybillData) {
            return [];
        }

        $waybill = json_decode($waybillData, true);
        if (!$waybill || !is_array($waybill)) {
            return [];
        }

        $result = [];
        foreach ($waybill as $index => $point) {
            if (isset($point['town_name'])) {
                $result[] = [
                    'town' => $this->getOrCreateTown($point['town_name']),
                    'excursion' => '',
                    'bold' => $point['is_bold'] ?? false
                ];
            }
        }

        return $result;
    }

    /**
     * Получение или создание города
     */
    private function getOrCreateTown($townName)
    {
        $town = Towns::where('name', $townName)->first();
        
        if (!$town) {
            $town = new Towns();
            $town->name = $townName;
            $town->save();
        }

        return $town->id;
    }

    /**
     * Синхронизация заезда с системой (аналог fixCheckin из RiverCrs)
     */
    public function fixCheckin($checkin_id)
    {
        $t = 'mcmraak_rivercrs_checkins_memory';
        $checkin = DB::table($t)->where('checkin_id', $checkin_id)->first();
        $now = date('Y-m-d h:i:s');
        if (!$checkin) {
            DB::table($t)->insert([
                'checkin_id' => $checkin_id,
                'updated_at' => $now
            ]);
        } else {
            DB::table($t)->where('checkin_id', $checkin_id)->update([
                'updated_at' => $now
            ]);
        }
    }

    /**
     * Получение статистики импорта
     */
    public function getStats()
    {
        return $this->stats;
    }
}