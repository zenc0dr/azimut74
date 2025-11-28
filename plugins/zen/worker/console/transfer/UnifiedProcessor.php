<?php namespace Zen\Worker\Console\transfer;

use Zen\Worker\Classes\ProcessLog;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use DB;
use Carbon\Carbon;

/**
 * Единый процессор для всех источников
 * Работает с единой структурой SQLite баз
 */
class UnifiedProcessor extends TransferProcessor
{
    /**
     * Основной метод обработки всех круизов из SQLite
     */
    public function process()
    {
        ProcessLog::add("Обработка заездов {$this->sourceName} из SQLite (UnifiedProcessor)");
        
        $cruises = $this->db->getAllCruises();
        $totalCruises = count($cruises);
        
        ProcessLog::add("Найдено заездов для обработки: " . $totalCruises);
        
        $errorsCount = 0;
        $processedCount = 0;
        
        foreach ($cruises as $cruise) {
            try {
                $cruiseId = $cruise['id'];
                $checkinId = $this->importCruise($cruise);
                
                if ($checkinId) {
                    $processedCount++;
                    ProcessLog::add("Обработка заезда {$this->edsCode}:$cruiseId завершена успешно");
                } else {
                    $errorsCount++;
                    ProcessLog::add("Ошибка обработки заезда {$this->edsCode}:$cruiseId");
                }
            } catch (\Exception $e) {
                $errorsCount++;
                ProcessLog::add("Исключение при обработке заезда {$this->edsCode}:{$cruise['id']}: " . $e->getMessage());
            }
        }
        
        ProcessLog::add("Обработка всех заездов {$this->sourceName} завершена. Обработано: $processedCount из $totalCruises, ошибок: $errorsCount");
    }
    
    /**
     * Импорт одного круиза
     * @param array $cruise Данные круиза из SQLite
     * @return int|null ID созданного/обновленного заезда или null при ошибке
     */
    protected function importCruise($cruise)
    {
        $cruiseId = $cruise['id'];
        $shipId = $cruise['ship_id'];
        
        // Получаем теплоход из SQLite
        $shipData = $this->db->getShipBySourceId($shipId);
        
        if (!$shipData) {
            ProcessLog::add("Теплоход с ID $shipId не найден в SQLite для круиза $cruiseId");
            return null;
        }
        
        ProcessLog::add("Обработка заезда {$this->edsCode}:$cruiseId (теплоход: {$shipData['name']})");
        
        // Получаем или создаем теплоход в MySQL
        $ship = $this->getMotorship($shipData['name'], $shipId);
        
        // Проверка исключения теплохода (не считается ошибкой, просто пропускаем)
        if (!$ship) {
            ProcessLog::add("Теплоход {$shipData['name']} исключён");
            return null;
        }
        
        // Получаем или создаем заезд
        $checkin = $this->getOrCreateCheckin($cruiseId);
        
        // Обработка дат
        $dateStart = null;
        $dateEnd = null;
        
        if (!empty($cruise['date_start'])) {
            try {
                // Используем master()->carbon() для совместимости
                if (function_exists('master') && method_exists(master(), 'carbon')) {
                    $dateStart = master()->carbon($cruise['date_start'])->toDateTimeString();
                } else {
                    $dateStart = Carbon::parse($cruise['date_start'])->toDateTimeString();
                }
            } catch (\Exception $e) {
                ProcessLog::add("Ошибка парсинга date_start для круиза $cruiseId: " . $e->getMessage());
            }
        }
        
        if (!empty($cruise['date_end'])) {
            try {
                if (function_exists('master') && method_exists(master(), 'carbon')) {
                    $dateEnd = master()->carbon($cruise['date_end'])->toDateTimeString();
                } else {
                    $dateEnd = Carbon::parse($cruise['date_end'])->toDateTimeString();
                }
            } catch (\Exception $e) {
                ProcessLog::add("Ошибка парсинга date_end для круиза $cruiseId: " . $e->getMessage());
            }
        }
        
        if (!$dateStart || !$dateEnd) {
            ProcessLog::add("Ошибка данных! --- cruise_id:$cruiseId - Отсутствуют даты, заезд игнорирован.");
            return null;
        }
        
        // Обработка маршрута
        $waybill = $this->processWaybillData($cruise['waybill_data'] ?? '');
        
        // Если waybill_data пустой или не обработался, пытаемся создать маршрут из поля route или названия
        if (!$waybill || empty($waybill) || count($waybill) < 2) {
            $waybillFromRoute = $this->createWaybillFromRoute($cruise['route'] ?? '', $cruise['name'] ?? '');
            if ($waybillFromRoute && count($waybillFromRoute) >= 2) {
                $waybill = $waybillFromRoute;
                ProcessLog::add("Маршрут создан из route/name для круиза $cruiseId");
            }
        }
        
        // Проверка валидности маршрута
        if (!$waybill || empty($waybill) || count($waybill) < 2) {
            ProcessLog::add("Ошибка данных! --- cruise_id:$cruiseId - Отсутствует маршрут, заезд игнорирован.");
            return null;
        }
        
        ProcessLog::add("Маршрут получен");
        
        // Проверяем наличие цен ДО создания заезда
        $prices = $this->db->getPricesByCruiseId($cruiseId);
        if (empty($prices)) {
            ProcessLog::add("Для заезда {$this->edsCode}:$cruiseId отсутствуют цены, заезд пропущен.");
            return null;
        }
        
        // Заполняем поля Checkin
        $checkin->date = $dateStart;
        $checkin->dateb = $dateEnd;
        $checkin->desc_1 = $cruise['schedule_html'] ?? '';
        $checkin->motorship_id = $ship->id;
        $checkin->active = 1;
        $checkin->eds_code = $this->edsCode;
        $checkin->eds_id = $cruiseId;
        $checkin->waybill_id = $waybill;
        $checkin->createCache = false; // Отключаем кеширование до импорта цен
        $checkin->save();
        
        $this->fixCheckin($checkin->id);
        
        ProcessLog::add("Заезд добавлен в базу. Обработка цен...");
        
        // Импорт цен из SQLite
        $pricesImported = $this->importPrices($checkin->id, $cruiseId, $ship->id);
        
        // Цены должны быть, но на всякий случай проверяем
        if (!$pricesImported) {
            ProcessLog::add("⚠️  Для заезда {$this->edsCode}:$cruiseId не удалось импортировать цены, заезд деактивирован.");
            $checkin->active = 0;
            $checkin->createCache = false;
            $checkin->save();
        } else {
            // Очищаем кеш и пересоздаём его с правильными данными
            $this->clearCheckinCache($checkin->id);
            $this->rebuildCheckinCache($checkin->id);
            
            ProcessLog::add("Кеш для заезда {$checkin->id} обновлён после импорта цен");
        }
        
        return $checkin->id;
    }
    
    /**
     * Импорт цен для круиза
     * @param int $checkinId ID заезда в MySQL
     * @param int $cruiseId ID круиза в SQLite
     * @param int $shipId ID теплохода в MySQL
     * @return bool true если цены успешно импортированы, false если цен нет
     */
    protected function importPrices($checkinId, $cruiseId, $shipId)
    {
        // Получаем цены из SQLite
        $prices = $this->db->getPricesByCruiseId($cruiseId);
        
        if (empty($prices)) {
            return false; // Цен нет
        }
        
        // Создаем маппинг категорий кают и обрабатываем палубы
        $cabinMapping = [];
        $cabinDeckMapping = []; // Маппинг cabinId => deck_id из SQLite
        
        foreach ($prices as $price) {
            $cabinCategoryId = $price['cabin_category_id'] ?? null;
            $cabinCategoryName = $price['cabin_category_name'] ?? '';
            $places = (int)($price['cabin_category_places'] ?? 1);
            
            // Если уже обработали эту категорию, пропускаем
            if (isset($cabinMapping[$cabinCategoryId])) {
                continue;
            }
            
            // Получаем или создаем категорию кают с использованием ID источника
            $cabinId = $this->getCabinCategory($cabinCategoryId, $cabinCategoryName, $shipId, $places);
            
            if ($cabinId) {
                $cabinMapping[$cabinCategoryId] = $cabinId;
                
                // Работа с палубами - используем точные данные из SQLite
                $deckId = $price['deck_id'] ?? null;
                $deckName = $price['deck_name'] ?? null;
                
                if ($deckId || $deckName) {
                    // Если есть deck_id, используем его для получения палубы
                    if ($deckId) {
                        // Получаем палубу по ID из SQLite (нужно найти в MySQL по названию)
                        if ($deckName) {
                            $deck = $this->getDeck($deckName);
                            if ($deck) {
                                $this->deckPivotCheck($cabinId, $deck->id);
                                $cabinDeckMapping[$cabinId] = $deck->id;
                                ProcessLog::add("Создана связь каюты $cabinId ({$cabinCategoryName}) с палубой {$deck->id} ({$deckName})");
                            }
                        }
                    } elseif ($deckName) {
                        // Если есть только название палубы
                        $deck = $this->getDeck($deckName);
                        if ($deck) {
                            $this->deckPivotCheck($cabinId, $deck->id);
                            $cabinDeckMapping[$cabinId] = $deck->id;
                            ProcessLog::add("Создана связь каюты $cabinId ({$cabinCategoryName}) с палубой {$deck->id} ({$deckName})");
                        }
                    }
                }
            }
        }
        
        // Подготавливаем данные для вставки
        $insert_prices = [];
        $nprices_count = 0; // Счетчик цен с палубами
        
        foreach ($prices as $price) {
            $cabinCategoryId = $price['cabin_category_id'] ?? null;
            
            // Проверяем, что категория кают была найдена
            if (!isset($cabinMapping[$cabinCategoryId])) {
                continue;
            }
            
            $cabinId = $cabinMapping[$cabinCategoryId];
            $priceValue = (int)($price['price_value'] ?? 0);
            $priceExtra = !empty($price['price_extra']) ? (int)$price['price_extra'] : null;
            $placesQnt = (int)($price['places_qnt'] ?? 1);
            
            if ($cabinId && $priceValue > 0) {
                $priceData = [
                    'checkin_id' => $checkinId,
                    'cabin_id' => $cabinId,
                    'price_a' => $priceValue
                ];
                
                if ($priceExtra) {
                    $priceData['price_b'] = $priceExtra;
                }
                
                $insert_prices[] = $priceData;
                
                // Сохраняем цену с палубой в nprices (если есть информация о палубе)
                $deckId = $cabinDeckMapping[$cabinId] ?? null;
                
                // Если deck_id не найден в маппинге, пытаемся получить из цены
                if (!$deckId) {
                    $deckName = $price['deck_name'] ?? null;
                    if ($deckName) {
                        $deck = $this->getDeck($deckName);
                        if ($deck) {
                            $deckId = $deck->id;
                        }
                    }
                }
                
                if ($deckId) {
                    // Сохраняем в nprices через DeckPricesPatch
                    if ($this->savePriceWithDeck($checkinId, $cabinId, $deckId, $priceValue, $placesQnt)) {
                        $nprices_count++;
                    }
                }
            }
        }
        
        if (!empty($insert_prices)) {
            // Удаляем старые цены и вставляем новые
            DB::table('mcmraak_rivercrs_pricing')
                ->where('checkin_id', $checkinId)
                ->delete();
            
            // Удаляем старые цены с палубами для этого заезда
            DB::table('mcmraak_rivercrs_nprices')
                ->where('checkin_id', $checkinId)
                ->delete();
            
            DB::table('mcmraak_rivercrs_pricing')
                ->insert($insert_prices);
            
            ProcessLog::add("Цены для заезда $cruiseId: добавлено " . count($insert_prices) . " цен в pricing, $nprices_count цен с палубами в nprices");
            return true; // Цены успешно импортированы
        }
        
        ProcessLog::add("Валидных цен для заезда $cruiseId не найдено");
        return false; // Валидных цен не найдено
    }
    
    /**
     * Обработка данных путевого листа из JSON
     */
    protected function processWaybillData($waybillData)
    {
        if (!$waybillData) {
            return [];
        }
        
        $waybill = json_decode($waybillData, true);
        if (!$waybill || !is_array($waybill)) {
            if ($waybillData) {
                $jsonError = json_last_error();
                if ($jsonError !== JSON_ERROR_NONE) {
                    ProcessLog::add("Ошибка декодирования waybill_data (JSON error: $jsonError)");
                }
            }
            return [];
        }
        
        $result = [];
        foreach ($waybill as $index => $point) {
            if (!is_array($point)) {
                continue;
            }
            
            $townId = null;
            
            // Если есть town как ID (число), используем его напрямую
            if (isset($point['town']) && is_numeric($point['town'])) {
                $townId = (int)$point['town'];
            } 
            // Если есть town_name или portName, получаем ID через getTownId
            elseif (isset($point['town_name']) || isset($point['portName'])) {
                $townName = $point['town_name'] ?? $point['portName'];
                if ($townName) {
                    $townId = $this->getTownId($townName);
                }
            }
            
            // Если не удалось получить townId, пропускаем точку
            if (!$townId) {
                continue;
            }
            
            $excursion = $point['excursion'] ?? '';
            $bold = $point['bold'] ?? 0;
            
            // Если bold не указан, делаем первый и последний элемент bold
            if (!isset($point['bold']) && ($index === 0 || $index === count($waybill) - 1)) {
                $bold = 1;
            }
            
            $result[] = [
                'town' => $townId,
                'excursion' => $excursion,
                'bold' => $bold
            ];
        }
        
        return $result;
    }
    
    /**
     * Создание маршрута из поля route или названия круиза
     * Используется как fallback, если waybill_data отсутствует
     */
    protected function createWaybillFromRoute($route, $name)
    {
        // Сначала пытаемся использовать поле route
        $routeString = $route;
        
        // Если route пустой, используем название круиза
        if (empty($routeString)) {
            $routeString = $name;
        }
        
        if (empty($routeString)) {
            return [];
        }
        
        // Разбиваем маршрут по разделителю " — " или " - "
        $routeArray = [];
        if (strpos($routeString, ' — ') !== false) {
            $routeArray = explode(' — ', $routeString);
        } elseif (strpos($routeString, ' - ') !== false) {
            $routeArray = explode(' - ', $routeString);
        } else {
            // Если нет разделителя, возвращаем пустой массив
            return [];
        }
        
        $waybill = [];
        foreach ($routeArray as $index => $townName) {
            // Убираем информацию в скобках (например, "(2 дня)")
            $townName = preg_replace('/\s*\([^)]+\)\s*/u', '', $townName);
            $townName = trim($townName);
            
            if (empty($townName)) {
                continue;
            }
            
            $townId = $this->getTownId($townName);
            if (!$townId) {
                continue;
            }
            
            $waybill[] = [
                'town' => $townId,
                'excursion' => '',
                'bold' => ($index === 0 || $index === count($routeArray) - 1) ? 1 : 0
            ];
        }
        
        return count($waybill) >= 2 ? $waybill : [];
    }
}

