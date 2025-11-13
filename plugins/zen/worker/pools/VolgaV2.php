<?php

namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Zen\Worker\Console\volga\VolgaDatabase;
use Zen\Worker\Classes\ProcessLog;
use Zen\Worker\Models\ErrorLog;
use Zen\Worker\Models\Stream;
use DB;
use Carbon\Carbon;
use Exception;
use Yaml;

class VolgaV2 extends Volga
{
    public function fillVolgaCruises()
    {
        ProcessLog::add('Обработка заездов Volga из SQLite');
        
        $db = new VolgaDatabase();
        $cruises = $db->getAllCruises();
        $totalCruises = count($cruises);
        
        ProcessLog::add("Найдено заездов для обработки: " . $totalCruises);
        
        // Инициализация файла состояния
        $this->initStateFile($totalCruises);
        
        $errorsCount = 0;
        $processedCount = 0;
        
        foreach ($cruises as $cruise) {
            $volga_ship_id = $cruise['volga_ship_id'];
            $volga_cruise_id = $cruise['volga_cruise_id'];
            $volga_ship = $db->getShipByVolgaId($volga_ship_id);
            
            if (!$volga_ship) {
                $this->logError(
                    "Теплоход с ID $volga_ship_id не найден в SQLite",
                    [
                        'volga_ship_id' => $volga_ship_id,
                        'volga_cruise_id' => $volga_cruise_id
                    ]
                );
                ProcessLog::add("Теплоход с ID $volga_ship_id не найден в SQLite");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }
            
            ProcessLog::add("Обработка заезда volga:$volga_cruise_id (теплоход: {$volga_ship['name']})");
            
            $ship = $this->getMotorship($volga_ship['name'], 'volga', $volga_ship_id);
            
            // Проверка исключения теплохода (не считается ошибкой, просто пропускаем)
            if (!$ship) {
                ProcessLog::add("Теплоход {$volga_ship['name']} исключён");
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }

            $checkin = Checkin::where('eds_code', 'volga')
                ->where('eds_id', $volga_cruise_id)
                ->first();

            if (!$checkin) {
                $checkin = new Checkin;
            }

            // Обработка дат
            $dateStart = null;
            $dateEnd = null;

            if (!empty($cruise['date_start'])) {
                try {
                    // Используем master()->carbon() для совместимости с GamaV3
                    if (function_exists('master') && method_exists(master(), 'carbon')) {
                        $dateStart = master()->carbon($cruise['date_start'])->toDateTimeString();
                    } else {
                        $dateStart = Carbon::parse($cruise['date_start'])->toDateTimeString();
                    }
                } catch (\Exception $e) {
                    ProcessLog::add("Ошибка парсинга date_start для круиза $volga_cruise_id: " . $e->getMessage());
                }
            }

            if (!empty($cruise['date_end'])) {
                try {
                    // Используем master()->carbon() для совместимости с GamaV3
                    if (function_exists('master') && method_exists(master(), 'carbon')) {
                        $dateEnd = master()->carbon($cruise['date_end'])->toDateTimeString();
                    } else {
                        $dateEnd = Carbon::parse($cruise['date_end'])->toDateTimeString();
                    }
                } catch (\Exception $e) {
                    ProcessLog::add("Ошибка парсинга date_end для круиза $volga_cruise_id: " . $e->getMessage());
                }
            }

            if (!$dateStart || !$dateEnd) {
                $this->logError(
                    "Отсутствуют даты для круиза volga:$volga_cruise_id",
                    [
                        'volga_cruise_id' => $volga_cruise_id,
                        'volga_ship_id' => $volga_ship_id,
                        'date_start_raw' => $cruise['date_start'] ?? null,
                        'date_end_raw' => $cruise['date_end'] ?? null
                    ]
                );
                ProcessLog::add("Ошибка данных! --- cruise_id:$volga_cruise_id - Отсутствуют даты, заезд игнорирован.");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }

            // Обработка маршрута
            $waybill = $this->processWaybillData($cruise['waybill_data']);
            
            // Проверка валидности маршрута
            if (!$waybill || empty($waybill) || count($waybill) < 2) {
                $this->logError(
                    "Отсутствует или некорректный маршрут для круиза volga:$volga_cruise_id",
                    [
                        'volga_cruise_id' => $volga_cruise_id,
                        'volga_ship_id' => $volga_ship_id,
                        'waybill_data' => $cruise['waybill_data'] ?? null,
                        'waybill_points_count' => $waybill ? count($waybill) : 0
                    ]
                );
                ProcessLog::add("Ошибка данных! --- cruise_id:$volga_cruise_id - Отсутствует маршрут, заезд игнорирован.");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }
            
            ProcessLog::add("Маршрут получен");
            
            $checkin->date = $dateStart;
            $checkin->dateb = $dateEnd;
            $checkin->desc_1 = '';
            $checkin->motorship_id = $ship->id;
            $checkin->active = 1;
            $checkin->eds_code = 'volga';
            $checkin->eds_id = $volga_cruise_id;
            $checkin->waybill_id = $waybill;
            $checkin->save();

            $this->fixCheckin($checkin->id);

            ProcessLog::add("Заезд добавлен в базу. Обработка цен...");

            // Импорт цен из SQLite
            $pricesImported = $this->importPricesForCruise($checkin->id, $volga_cruise_id, $db, $ship->id);
            
            // Если цены не найдены, деактивируем заезд
            if (!$pricesImported) {
                $this->logError(
                    "Отсутствуют цены для заезда volga:$volga_cruise_id, заезд деактивирован",
                    [
                        'volga_cruise_id' => $volga_cruise_id,
                        'checkin_id' => $checkin->id,
                        'motorship_id' => $ship->id
                    ]
                );
                ProcessLog::add("Для заезда volga:$volga_cruise_id отсутствуют цены, заезд деактивирован.");
                $checkin->active = 0;
                $checkin->save();
            } else {
                ProcessLog::add("Обработка заезда volga:$volga_cruise_id завершена.");
            }
            
            // Обновляем прогресс после обработки каждого круиза
            $processedCount++;
            $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
        }
        
        // Финальное обновление состояния - успешное завершение
        $this->updateStateFile($processedCount, $totalCruises, $errorsCount, true);
        ProcessLog::add("Обработка всех заездов Volga завершена. Обработано: $processedCount из $totalCruises, ошибок: $errorsCount");
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
            return [];
        }

        $result = [];
        foreach ($waybill as $index => $point) {
            if (isset($point['town_name'])) {
                $townId = $this->getTownId($point['town_name'], 'volga');
                $result[] = [
                    'town' => $townId,
                    'excursion' => $point['excursion'] ?? '',
                    'bold' => $point['bold'] ?? 0
                ];
            } elseif (isset($point['town'])) {
                // Если уже есть town_id, используем его
                $result[] = [
                    'town' => $point['town'],
                    'excursion' => $point['excursion'] ?? '',
                    'bold' => $point['bold'] ?? 0
                ];
            }
        }

        return $result;
    }

    /**
     * Импорт цен для заезда из SQLite
     * @return bool true если цены найдены и импортированы, false если цен нет
     */
    private function importPricesForCruise($checkinId, $volgaCruiseId, $db, $shipId)
    {
        // Получаем цены из SQLite
        $prices = $db->getPricesByCruiseId($volgaCruiseId);
        
        if (empty($prices)) {
            return false; // Цен нет
        }

        // Создаем маппинг категорий кают и обрабатываем палубы
        $cabinMapping = [];
        $cabinDeckMapping = []; // Маппинг cabinId => deck_name из SQLite (точные данные)
        
        foreach ($prices as $price) {
            $cabinCategoryId = $price['cabin_category_id'];
            
            // Если уже обработали эту категорию, пропускаем
            if (isset($cabinMapping[$cabinCategoryId])) {
                continue;
            }
            
            // Получаем название категории
            $categoryName = '';
            if (!empty($price['category_name'])) {
                $categoryName = $price['category_name'];
            }
            
            // Если название пустое, используем только ID
            if (empty($categoryName)) {
                ProcessLog::add("Предупреждение: для категории $cabinCategoryId отсутствует название, используем только ID");
                $categoryName = $cabinCategoryId;
            }
            
            // Передаём только название категории БЕЗ ID (как в InfoflotV2)
            // getCabinCategoryId создаст volga_name автоматически из переданного имени
            $places = $price['places_main_count'] ?? 1;
            
            $cabinId = $this->getCabinCategoryId(
                $categoryName,
                $shipId,
                'volga',
                $places
            );
            
            if ($cabinId) {
                $cabinMapping[$cabinCategoryId] = $cabinId;
                
                // Работа с палубами - используем точные данные из SQLite
                if (isset($price['deck_name']) && !empty($price['deck_name'])) {
                    $deck = $this->getDeck($price['deck_name']);
                    if ($deck) {
                        $this->deckPivotCheck($cabinId, $deck->id);
                        // Сохраняем точную информацию о палубе для этой каюты
                        $cabinDeckMapping[$cabinId] = $price['deck_name'];
                        ProcessLog::add("Создана связь каюты $cabinId ({$categoryName}) с палубой {$deck->id} ({$price['deck_name']})");
                    }
                }
            }
        }

        // Подготавливаем данные для вставки
        $insert_prices = [];
        foreach ($prices as $price) {
            $cabinCategoryId = $price['cabin_category_id'];
            
            // Проверяем, что категория кают была найдена
            if (!isset($cabinMapping[$cabinCategoryId])) {
                continue;
            }
            
            $cabinId = $cabinMapping[$cabinCategoryId];
            
            if ($cabinId) {
                $priceA = (int)($price['price_value'] ?? 0);
                $priceB = isset($price['price2_value']) && $price['price2_value'] !== null 
                    ? (int)$price['price2_value'] 
                    : null;
                
                if ($priceA > 0) {
                    $insert_prices[] = [
                        'checkin_id' => $checkinId,
                        'cabin_id' => $cabinId,
                        'price_a' => $priceA,
                        'price_b' => $priceB
                    ];
                }
            }
        }

        if (!empty($insert_prices)) {
            // Удаляем старые цены и вставляем новые
            DB::table('mcmraak_rivercrs_pricing')
                ->where('checkin_id', $checkinId)
                ->delete();

            DB::table('mcmraak_rivercrs_pricing')
                ->insert($insert_prices);
            
            // Восстанавливаем связи кают с палубами для всех кают с ценами, используя точные данные из SQLite
            $this->restoreDeckLinksForCheckin($checkinId, $shipId, $cabinMapping, $cabinDeckMapping);
            
            ProcessLog::add("Цены для заезда $volgaCruiseId: добавлено " . count($insert_prices) . " цен");
            return true; // Цены успешно импортированы
        }
        
        ProcessLog::add("Валидных цен для заезда $volgaCruiseId не найдено");
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
     * Логирование ошибки в таблицу zen_worker_errors
     */
    private function logError($errorMessage, $cruiseData = [])
    {
        try {
            // Получаем stream_id по code='Volga'
            $stream = Stream::where('code', 'Volga')->first();
            
            if (!$stream) {
                // Если stream не найден, просто логируем в ProcessLog
                ProcessLog::add("Не удалось записать ошибку в ErrorLog: stream 'Volga' не найден. Ошибка: $errorMessage");
                return;
            }
            
            $errorLog = new ErrorLog;
            $errorLog->stream_id = $stream->id;
            $errorLog->call = 'VolgaV2@fillVolgaCruises';
            $errorLog->data = $cruiseData; // Автоматически JSON через setDataAttribute
            $errorLog->error = $errorMessage;
            $errorLog->save();
            
        } catch (\Exception $e) {
            // Если не удалось записать в ErrorLog, хотя бы логируем в ProcessLog
            ProcessLog::add("Ошибка при записи в ErrorLog: " . $e->getMessage());
        }
    }

    /**
     * Инициализация файла состояния
     */
    private function initStateFile($totalCruises)
    {
        $statePath = storage_path('worker/VolgaState.yaml');
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
        $statePath = storage_path('worker/VolgaState.yaml');
        
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

