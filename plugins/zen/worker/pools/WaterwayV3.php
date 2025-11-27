<?php

namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Zen\Worker\Console\waterway\WaterwayDatabase;
use Zen\Worker\Classes\ProcessLog;
use DB;
use Carbon\Carbon;
use Exception;
use Yaml;

/**
 * WaterwayV3 - независимый пул для импорта данных из SQLite в MySQL (Фаза 2)
 * Наследуется от RiverCrs, а не от Waterway, чтобы исключить методы работы с API
 */
class WaterwayV3 extends RiverCrs
{
    public function fillWaterwayCruises()
    {
        ProcessLog::add('Обработка заездов Waterway из SQLite (WaterwayV3)');
        
        $db = new WaterwayDatabase();
        $cruises = $db->getAllCruises();
        $totalCruises = count($cruises);
        
        ProcessLog::add("Найдено заездов для обработки: " . $totalCruises);
        
        // Инициализация файла состояния
        $this->initStateFile($totalCruises);
        
        $errorsCount = 0;
        $processedCount = 0;
        
        foreach ($cruises as $cruise) {
            $waterway_ship_id = $cruise['waterway_ship_id'];
            $waterway_cruise_id = $cruise['waterway_cruise_id'];
            $waterway_ship = $db->getShipByWaterwayId($waterway_ship_id);
            
            if (!$waterway_ship) {
                ProcessLog::add("Теплоход с ID $waterway_ship_id не найден в SQLite");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }
            
            ProcessLog::add("Обработка заезда waterway:$waterway_cruise_id (теплоход: {$waterway_ship['name']})");
            
            $ship = $this->getMotorship($waterway_ship['name'], 'waterway_id', $waterway_ship_id);
            
            // Проверка исключения теплохода (не считается ошибкой, просто пропускаем)
            if (!$ship) {
                ProcessLog::add("Теплоход {$waterway_ship['name']} исключён");
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }

            $checkin = Checkin::where('eds_code', 'waterway')
                ->where('eds_id', $waterway_cruise_id)
                ->first();

            if (!$checkin) {
                $checkin = new Checkin;
            }

            // Обработка дат
            $dateStart = null;
            $dateEnd = null;

            // Используем точные даты если есть, иначе обычные
            $dateStartRaw = $cruise['date_start_precise'] ?? $cruise['date_start'];
            $dateEndRaw = $cruise['date_end_precise'] ?? $cruise['date_end'];

            // Очищаем дублирование времени (если есть формат "2022-08-14 19:00:00 19:00:00")
            $dateStartRaw = $this->cleanDuplicateTime($dateStartRaw);
            $dateEndRaw = $this->cleanDuplicateTime($dateEndRaw);

            if (!empty($dateStartRaw)) {
                try {
                    // Используем master()->carbon() для совместимости с GamaV3
                    if (function_exists('master') && method_exists(master(), 'carbon')) {
                        $dateStart = master()->carbon($dateStartRaw)->toDateTimeString();
                    } else {
                        $dateStart = Carbon::parse($dateStartRaw)->toDateTimeString();
                    }
                } catch (\Exception $e) {
                    ProcessLog::add("Ошибка парсинга date_start для круиза $waterway_cruise_id: " . $e->getMessage());
                }
            }

            if (!empty($dateEndRaw)) {
                try {
                    // Используем master()->carbon() для совместимости с GamaV3
                    if (function_exists('master') && method_exists(master(), 'carbon')) {
                        $dateEnd = master()->carbon($dateEndRaw)->toDateTimeString();
                    } else {
                        $dateEnd = Carbon::parse($dateEndRaw)->toDateTimeString();
                    }
                } catch (\Exception $e) {
                    ProcessLog::add("Ошибка парсинга date_end для круиза $waterway_cruise_id: " . $e->getMessage());
                }
            }

            if (!$dateStart || !$dateEnd) {
                ProcessLog::add("Ошибка данных! --- cruise_id:$waterway_cruise_id - Отсутствуют даты, заезд игнорирован.");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }

            // Обработка маршрута
            $waybill = $this->processWaybillData($cruise['waybill_data']);
            
            // Если waybill_data пустой или не обработался, пытаемся создать маршрут из поля route или названия
            if (!$waybill || empty($waybill) || count($waybill) < 2) {
                $waybillFromRoute = $this->createWaybillFromRoute($cruise['route'] ?? '', $cruise['name'] ?? '');
                if ($waybillFromRoute && count($waybillFromRoute) >= 2) {
                    $waybill = $waybillFromRoute;
                    ProcessLog::add("Маршрут создан из route/name для круиза $waterway_cruise_id");
                }
            }
            
            // Проверка валидности маршрута
            if (!$waybill || empty($waybill) || count($waybill) < 2) {
                $waybillDataStatus = !empty($cruise['waybill_data']) ? 'есть' : 'нет';
                $routeStatus = !empty($cruise['route']) ? 'есть' : 'нет';
                $nameStatus = !empty($cruise['name']) ? 'есть' : 'нет';
                ProcessLog::add("Ошибка данных! --- cruise_id:$waterway_cruise_id - Отсутствует маршрут (waybill_data: $waybillDataStatus, route: $routeStatus, name: $nameStatus), заезд игнорирован.");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }
            
            ProcessLog::add("Маршрут получен");
            
            // Проверяем наличие цен ДО создания заезда
            $prices = $db->getPricesByCruiseId($waterway_cruise_id);
            if (empty($prices)) {
                ProcessLog::add("Для заезда waterway:$waterway_cruise_id отсутствуют цены, заезд пропущен.");
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }
            
            // Используем schedule_html из SQLite для desc_1
            $scheduleHtml = $cruise['schedule_html'] ?? '';
            
            $checkin->date = $dateStart;
            $checkin->dateb = $dateEnd;
            $checkin->desc_1 = $scheduleHtml;
            $checkin->motorship_id = $ship->id;
            $checkin->active = 1;
            $checkin->eds_code = 'waterway';
            $checkin->eds_id = $waterway_cruise_id;
            $checkin->waybill_id = $waybill;
            $checkin->createCache = false; // Отключаем кеширование до импорта цен
            $checkin->save();

            $this->fixCheckin($checkin->id);

            ProcessLog::add("Заезд добавлен в базу. Обработка цен...");

            // Импорт цен из SQLite (цены уже проверены выше)
            $pricesImported = $this->importPricesForCruise($checkin->id, $waterway_cruise_id, $db, $ship->id);
            
            // Цены должны быть, но на всякий случай проверяем
            if (!$pricesImported) {
                ProcessLog::add("⚠️  Для заезда waterway:$waterway_cruise_id не удалось импортировать цены, заезд деактивирован.");
                $checkin->active = 0;
                $checkin->createCache = false; // Кеш не нужен для деактивированного заезда
                $checkin->save();
            } else {
                // Очищаем кеш, созданный преждевременно в afterSave()
                // и пересоздаём его с правильными данными (цены и связи уже есть)
                $cabox = new \Zen\Cabox\Classes\Cabox('rivercrs');
                $cabox->del('rcrs:' . $checkin->id);
                $cabox->del('exist_array:' . $checkin->id);
                
                // Пересоздаём кеш с правильными данными
                // НЕ вызываем cachePrices() на фазе 2, так как он делает запросы к API
                // Данные уже есть в базе, кеш создастся автоматически при необходимости
                Checkin::getResult($checkin->id, true);
                
                ProcessLog::add("Кеш для заезда {$checkin->id} обновлён после импорта цен (без cachePrices на фазе 2)");
                ProcessLog::add("Обработка заезда waterway:$waterway_cruise_id завершена.");
            }
            
            $processedCount++;
            $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
        }
        
        // Финальное обновление состояния - успешное завершение
        $this->updateStateFile($processedCount, $totalCruises, $errorsCount, true);
        ProcessLog::add("Обработка всех заездов Waterway завершена. Обработано: $processedCount из $totalCruises, ошибок: $errorsCount");
    }

    /**
     * Очистка дублирования времени в строке даты
     * Исправляет формат "2022-08-14 19:00:00 19:00:00" в "2022-08-14 19:00:00"
     */
    private function cleanDuplicateTime($dateString)
    {
        if (empty($dateString)) {
            return $dateString;
        }
        
        // Если строка содержит два времени (паттерн: дата время время)
        // Используем регулярное выражение для поиска дублирования
        // Паттерн: YYYY-MM-DD HH:MM:SS HH:MM:SS
        if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \d{2}:\d{2}:\d{2}$/', $dateString, $matches)) {
            return $matches[1]; // Возвращаем только первую часть с датой и временем
        }
        
        return $dateString;
    }

    /**
     * Обработка данных путевого листа из JSON
     */
    private function processWaybillData($waybillData)
    {
        if (!$waybillData) {
            return [];
        }

        $waybill = json_decode($waybillData, true);
        if (!$waybill || !is_array($waybill)) {
            // Логируем ошибку декодирования только если данные есть
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
                    $townId = $this->getTownId($townName, 'waterway');
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
    private function createWaybillFromRoute($route, $name)
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
            
            $townId = $this->getTownId($townName, 'waterway');
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

    /**
     * Импорт цен для заезда из SQLite
     * @return bool true если цены найдены и импортированы, false если цен нет
     */
    private function importPricesForCruise($checkinId, $waterwayCruiseId, $db, $shipId)
    {
        // Получаем цены из SQLite
        $prices = $db->getPricesByCruiseId($waterwayCruiseId);
        
        if (empty($prices)) {
            return false; // Цен нет
        }

        // Создаем маппинг категорий кают и обрабатываем палубы
        $cabinMapping = [];
        $cabinDeckMapping = []; // Маппинг cabinId => deck_name из SQLite (точные данные)
        
        foreach ($prices as $price) {
            // В Waterway нет cabin_category_id, используем cabin_category_name как ключ
            $cabinCategoryName = $price['cabin_category_name'] ?? '';
            
            if (empty($cabinCategoryName)) {
                continue;
            }
            
            // Если уже обработали эту категорию, пропускаем
            if (isset($cabinMapping[$cabinCategoryName])) {
                continue;
            }
            
            // Получаем описание категории для создания каюты
            $cabinCategoryDesc = $price['cabin_category_desc'] ?? '';
            
            // Используем waterway_name для поиска/создания кают (как в старом скрипте)
            // Сначала ищем по waterway_name
            $cabin = Cabin::where('waterway_name', $cabinCategoryName)
                ->where('motorship_id', $shipId)
                ->first();
            
            // Если не найдено, ищем по category
            if (!$cabin) {
                $cabin = Cabin::where('category', $cabinCategoryName)
                    ->where('motorship_id', $shipId)
                    ->first();
            }
            
            // Если не найдено, создаём новую
            if (!$cabin) {
                $cabin = new Cabin;
                $cabin->motorship_id = $shipId;
                $cabin->category = $cabinCategoryName;
                $cabin->waterway_name = $cabinCategoryName;
                $cabin->desc = $cabinCategoryDesc;
                $cabin->save();
            }
            
            $cabinId = $cabin->id;
            $cabinMapping[$cabinCategoryName] = $cabinId;
            
            // Работа с палубами - используем точные данные из SQLite
            if (isset($price['deck_name']) && !empty($price['deck_name'])) {
                $deck = $this->getDeck($price['deck_name']);
                if ($deck) {
                    $this->deckPivotCheck($cabinId, $deck->id);
                    // Сохраняем точную информацию о палубе для этой каюты
                    $cabinDeckMapping[$cabinId] = $price['deck_name'];
                    ProcessLog::add("Создана связь каюты $cabinId ({$cabinCategoryName}) с палубой {$deck->id} ({$price['deck_name']})");
                }
            }
        }

        // Подготавливаем данные для вставки
        $insert_prices = [];
        $nprices_count = 0; // Счетчик цен с палубами
        
        foreach ($prices as $price) {
            $cabinCategoryName = $price['cabin_category_name'] ?? '';
            
            // Проверяем, что категория кают была найдена
            if (empty($cabinCategoryName) || !isset($cabinMapping[$cabinCategoryName])) {
                continue;
            }
            
            $cabinId = $cabinMapping[$cabinCategoryName];
            $priceValue = (int)($price['price_value'] ?? 0);
            
            if ($cabinId && $priceValue > 0) {
                $insert_prices[] = [
                    'checkin_id' => $checkinId,
                    'cabin_id' => $cabinId,
                    'price_a' => $priceValue
                ];
                
                // Сохраняем цену с палубой в nprices (если есть информация о палубе)
                $deckName = $price['deck_name'] ?? null;
                if ($deckName) {
                    $deck = $this->getDeck($deckName);
                    if ($deck) {
                        // places_qnt = 1 по умолчанию (для Waterway обычно 1 место в цене)
                        $places_qnt = 1;
                        
                        // Сохраняем в nprices через DeckPricesPatch
                        pricePatch()->setPrice($checkinId, $deck->id, $cabinId, $places_qnt, $priceValue);
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
            
            // Восстанавливаем связи кают с палубами для всех кают с ценами, используя точные данные из SQLite
            $this->restoreDeckLinksForCheckin($checkinId, $shipId, $cabinMapping, $cabinDeckMapping);
            
            ProcessLog::add("Цены для заезда $waterwayCruiseId: добавлено " . count($insert_prices) . " цен в pricing, $nprices_count цен с палубами в nprices");
            return true; // Цены успешно импортированы
        }
        
        ProcessLog::add("Валидных цен для заезда $waterwayCruiseId не найдено");
        return false; // Валидных цен не найдено
    }

    /**
     * Восстановление связей кают с палубами для заезда
     * Использует ТОЧНЫЕ данные из SQLite о палубах для каждой категории кают
     * Если точных данных нет, использует эталонную палубу как fallback
     * КРИТИЧНО: гарантирует, что все каюты с ценами имеют связи с палубами
     */
    private function restoreDeckLinksForCheckin($checkinId, $shipId, $cabinMapping, $cabinDeckMapping = [])
    {
        // Получаем все уникальные cabin_id из цен
        $cabinIdsWithPrices = DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $checkinId)
            ->distinct()
            ->pluck('cabin_id')
            ->toArray();

        if (empty($cabinIdsWithPrices)) {
            return;
        }

        // Находим эталонную палубу (fallback, если нет точных данных из SQLite)
        $referenceDeck = DB::table('mcmraak_rivercrs_decks_pivot')
            ->join('mcmraak_rivercrs_cabins', 'mcmraak_rivercrs_cabins.id', '=', 'mcmraak_rivercrs_decks_pivot.cabin_id')
            ->where('mcmraak_rivercrs_cabins.motorship_id', $shipId)
            ->select('mcmraak_rivercrs_decks_pivot.deck_id')
            ->first();

        $referenceDeckId = $referenceDeck ? $referenceDeck->deck_id : null;

        $restoredCount = 0;
        foreach ($cabinIdsWithPrices as $cabinId) {
            // ПРИОРИТЕТ 1: Используем точные данные из SQLite (если есть)
            if (isset($cabinDeckMapping[$cabinId]) && !empty($cabinDeckMapping[$cabinId])) {
                $deckName = $cabinDeckMapping[$cabinId];
                $deck = $this->getDeck($deckName);
                if ($deck) {
                    // Проверяем, есть ли уже связь с этой конкретной палубой
                    $hasExactLink = DB::table('mcmraak_rivercrs_decks_pivot')
                        ->where('cabin_id', $cabinId)
                        ->where('deck_id', $deck->id)
                        ->exists();
                    
                    if (!$hasExactLink) {
                        try {
                            $this->deckPivotCheck($cabinId, $deck->id);
                            $restoredCount++;
                            ProcessLog::add("Восстановлена связь каюты $cabinId с палубой {$deck->id} ({$deckName}) из SQLite");
                        } catch (\Exception $e) {
                            // Игнорируем ошибки дубликатов
                        }
                    }
                    continue; // Пропускаем fallback, так как точная связь обработана
                }
            }

            // ПРИОРИТЕТ 2: Используем эталонную палубу (fallback) только если нет ЛЮБОЙ связи
            $hasAnyLink = DB::table('mcmraak_rivercrs_decks_pivot')
                ->where('cabin_id', $cabinId)
                ->exists();

            if (!$hasAnyLink && $referenceDeckId) {
                try {
                    DB::table('mcmraak_rivercrs_decks_pivot')->insert([
                        'cabin_id' => $cabinId,
                        'deck_id' => $referenceDeckId
                    ]);
                    $restoredCount++;
                    ProcessLog::add("Восстановлена связь каюты $cabinId с эталонной палубой $referenceDeckId (fallback)");
                } catch (\Exception $e) {
                    // Игнорируем ошибки дубликатов
                }
            } elseif (!$hasAnyLink) {
                ProcessLog::add("Предупреждение: не удалось создать связь для cabin_id=$cabinId - нет доступных палуб для теплохода $shipId");
            }
        }

        if ($restoredCount > 0) {
            ProcessLog::add("Восстановлено связей кают с палубами для заезда $checkinId: $restoredCount");
        }
    }

    /**
     * Инициализация файла состояния
     */
    private function initStateFile($totalCruises)
    {
        $statePath = storage_path('worker/WaterwayState.yaml');
        $stateDir = dirname($statePath);
        
        // Создаём директорию если не существует
        if (!is_dir($stateDir)) {
            mkdir($stateDir, 0777, true);
        }
        
        $state = [
            [
                'progress_of' => $totalCruises,
                'progress_to' => 0,
                'errors_count' => 0,
                'success' => false,
                'updated_at' => time()
            ]
        ];
        
        $yaml = Yaml::render($state);
        file_put_contents($statePath, $yaml);
        
        ProcessLog::add("Файл состояния инициализирован: $totalCruises заездов для обработки");
    }

    /**
     * Обновление файла состояния
     */
    private function updateStateFile($progressOf, $progressTo, $errorsCount, $success)
    {
        $statePath = storage_path('worker/WaterwayState.yaml');
        
        if (!file_exists($statePath)) {
            // Если файл не существует, создаём его
            $this->initStateFile($progressOf);
        }
        
        $state = [
            [
                'progress_of' => $progressTo,
                'progress_to' => $progressOf,
                'errors_count' => $errorsCount,
                'success' => $success,
                'updated_at' => time()
            ]
        ];
        
        $yaml = Yaml::render($state);
        file_put_contents($statePath, $yaml);
    }
}

