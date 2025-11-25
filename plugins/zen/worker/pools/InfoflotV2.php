<?php

namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Zen\Worker\Console\infoflot\InfoflotDatabase;
use Zen\Worker\Classes\ProcessLog;
use Zen\Worker\Models\ErrorLog;
use Zen\Worker\Models\Stream;
use DB;
use Carbon\Carbon;
use Exception;
use Yaml;

class InfoflotV2 extends RiverCrs
{
    public function fillInfoflotCruises()
    {
        ProcessLog::add('Обработка заездов Infoflot из SQLite');

        $db = new InfoflotDatabase();
        $cruises = $db->getAllCruises();
        $totalCruises = count($cruises);

        ProcessLog::add("Найдено заездов для обработки: " . $totalCruises);
        
        // Инициализация файла состояния
        $this->initStateFile($totalCruises);
        
        $errorsCount = 0;
        $processedCount = 0;

        foreach ($cruises as $cruise) {
            $infoflot_ship_id = $cruise['infoflot_ship_id'];
            $infoflot_cruise_id = $cruise['id'];
            $infoflot_ship = $db->getShipByInfoflotId($infoflot_ship_id);

            if (!$infoflot_ship) {
                $this->logError(
                    "Теплоход с ID $infoflot_ship_id не найден в SQLite",
                    [
                        'infoflot_ship_id' => $infoflot_ship_id,
                        'infoflot_cruise_id' => $infoflot_cruise_id
                    ]
                );
                ProcessLog::add("Теплоход с ID $infoflot_ship_id не найден в SQLite");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }

            ProcessLog::add("Обработка заезда infoflot:$infoflot_cruise_id (теплоход: {$infoflot_ship['name']})");

            $ship = $this->getMotorship($infoflot_ship['name'], 'infoflot_id', $infoflot_ship_id);

            // Проверка исключения теплохода (не считается ошибкой, просто пропускаем)
            if (!$ship) {
                ProcessLog::add("Теплоход {$infoflot_ship['name']} исключён");
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }

            $checkin = Checkin::where('eds_code', 'infoflot')
                ->where('eds_id', $infoflot_cruise_id)
                ->first();

            if (!$checkin) {
                $checkin = new Checkin;
            }

            // Обработка дат
            $dateStart = null;
            $dateEnd = null;

            if (isset($cruise['date_start_timestamp']) && $cruise['date_start_timestamp']) {
                $dateStart = Carbon::createFromTimestamp($cruise['date_start_timestamp'])
                    ->setTimeZone('Europe/Moscow')
                    ->format('Y-m-d H:i:s');
            } elseif (isset($cruise['date_start'])) {
                $dateStart = Carbon::parse($cruise['date_start'])
                    ->setTimeZone('Europe/Moscow')
                    ->format('Y-m-d H:i:s');
            }

            if (isset($cruise['date_end_timestamp']) && $cruise['date_end_timestamp']) {
                $dateEnd = Carbon::createFromTimestamp($cruise['date_end_timestamp'])
                    ->setTimeZone('Europe/Moscow')
                    ->format('Y-m-d H:i:s');
            } elseif (isset($cruise['date_end'])) {
                $dateEnd = Carbon::parse($cruise['date_end'])
                    ->setTimeZone('Europe/Moscow')
                    ->format('Y-m-d H:i:s');
            }

            if (!$dateStart || !$dateEnd) {
                $this->logError(
                    "Отсутствуют даты для круиза infoflot:$infoflot_cruise_id",
                    [
                        'infoflot_cruise_id' => $infoflot_cruise_id,
                        'infoflot_ship_id' => $infoflot_ship_id,
                        'date_start_raw' => $cruise['date_start'] ?? null,
                        'date_end_raw' => $cruise['date_end'] ?? null,
                        'date_start_timestamp' => $cruise['date_start_timestamp'] ?? null,
                        'date_end_timestamp' => $cruise['date_end_timestamp'] ?? null
                    ]
                );
                ProcessLog::add("Ошибка данных! --- cruise_id:$infoflot_cruise_id - Отсутствуют даты, заезд игнорирован.");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }

            // Обработка маршрута
            $waybill = $this->processWaybillData($cruise['route'], $cruise['route_short'] ?? null);

            // Проверка валидности маршрута
            if (!$waybill || empty($waybill) || count($waybill) < 2) {
                $this->logError(
                    "Отсутствует или некорректный маршрут для круиза infoflot:$infoflot_cruise_id",
                    [
                        'infoflot_cruise_id' => $infoflot_cruise_id,
                        'infoflot_ship_id' => $infoflot_ship_id,
                        'route' => $cruise['route'] ?? null,
                        'route_short' => $cruise['route_short'] ?? null,
                        'waybill_points_count' => $waybill ? count($waybill) : 0
                    ]
                );
                ProcessLog::add("Ошибка данных! --- cruise_id:$infoflot_cruise_id - Отсутствует маршрут, заезд игнорирован.");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }

            ProcessLog::add("Маршрут получен");

            $checkin->date = $dateStart;
            $checkin->dateb = $dateEnd;
            $checkin->desc_1 = $cruise['description'] ?? '';
            $checkin->motorship_id = $ship->id;
            $checkin->active = 1;
            $checkin->eds_code = 'infoflot';
            $checkin->eds_id = $infoflot_cruise_id;
            $checkin->waybill_id = $waybill;
            $checkin->createCache = false; // Отключаем кеширование до импорта цен
            $checkin->save();

            $this->fixCheckin($checkin->id);

            ProcessLog::add("Заезд добавлен в базу. Обработка цен...");

            // Импорт цен из SQLite
            $pricesImported = $this->importPricesForCruise($checkin->id, $infoflot_cruise_id, $db, $ship->id);

            // Если цены не найдены, деактивируем заезд
            if (!$pricesImported) {
                $this->logError(
                    "Отсутствуют цены для заезда infoflot:$infoflot_cruise_id, заезд деактивирован",
                    [
                        'infoflot_cruise_id' => $infoflot_cruise_id,
                        'checkin_id' => $checkin->id,
                        'motorship_id' => $ship->id
                    ]
                );
                ProcessLog::add("Для заезда infoflot:$infoflot_cruise_id отсутствуют цены, заезд деактивирован.");
                $checkin->active = 0;
                $checkin->createCache = false; // Кеш не нужен для деактивированного заезда
                $checkin->save();
            } else {
                // Создаём кеш после импорта цен и связей кают с палубами
                Checkin::getResult($checkin->id, true);
                $checkin->cachePrices();
                ProcessLog::add("Обработка заезда infoflot:$infoflot_cruise_id завершена. Кеш создан.");
            }
            
            // Обновляем прогресс после обработки каждого круиза
            $processedCount++;
            $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
        }
        
        // Финальное обновление состояния - успешное завершение
        $this->updateStateFile($processedCount, $totalCruises, $errorsCount, true);
        ProcessLog::add("Обработка всех заездов Infoflot завершена. Обработано: $processedCount из $totalCruises, ошибок: $errorsCount");
    }

    /**
     * Обработка данных маршрута
     */
    private function processWaybillData($route, $routeShort = null)
    {
        if (!$route) {
            return [];
        }

        $routeArray = explode(' – ', $route);

        $routeShortArray = [];
        if ($routeShort) {
            $routeShortArray = explode(' – ', $routeShort);
        }

        $waybill = [];
        $key = 0;
        $max = count($routeArray) - 1;

        foreach ($routeArray as $point) {
            $town_id = $this->getTownId($point, 'infoflot');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => ($key == 0 || $key == $max || in_array($point, $routeShortArray)) ? 1 : 0
            ];
            $key++;
        }

        return $waybill;
    }

    /**
     * Импорт цен для заезда из SQLite
     * @return bool true если цены найдены и импортированы, false если цен нет
     */
    private function importPricesForCruise($checkinId, $infoflotCruiseId, $db, $shipId)
    {
        // Получаем цены из SQLite
        $prices = $db->getPricesByCruiseId($infoflotCruiseId);

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

            // Получаем название категории: сначала из category_name, потом из type_name
            $categoryName = '';
            if (!empty($price['category_name'])) {
                $categoryName = $price['category_name'];
            } elseif (!empty($price['type_name'])) {
                $categoryName = $price['type_name'];
            }

            // Если название пустое, используем только ID
            if (empty($categoryName)) {
                ProcessLog::add("Предупреждение: для категории $cabinCategoryId отсутствует название, используем только ID");
                $categoryName = $cabinCategoryId;
            }

            // КРИТИЧНО: Используем формат "name|id" для уникальности (как в GermesV2)
            // Это необходимо, так как в SQLite могут быть разные категории с одинаковыми названиями
            // Без ID разные категории будут маппиться в одну, создавая дубликаты
            $categoryNameWithId = $categoryName . '|' . $cabinCategoryId;

            $places = $price['places'] ?? 1;

            $cabinId = $this->getCabinCategoryId(
                $categoryNameWithId,
                $shipId,
                'infoflot',
                $places
            );

            // После создания/получения каюты, обновляем category на чистое название для отображения
            if ($cabinId) {
                $cabin = \Mcmraak\Rivercrs\Models\Cabins::find($cabinId);
                if ($cabin && ($cabin->category === $categoryNameWithId || empty($cabin->category))) {
                    // Обновляем category на чистое название (без ID) для корректного отображения в интерфейсе
                    $cabin->category = $categoryName;
                    $cabin->save();
                }
            }

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

            if ($cabinId && isset($price['price_adult'])) {
                $insert_prices[] = [
                    'checkin_id' => $checkinId,
                    'cabin_id' => $cabinId,
                    'price_a' => $price['price_adult']
                ];
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

            ProcessLog::add("Цены для заезда $infoflotCruiseId: добавлено " . count($insert_prices) . " цен");
            return true; // Цены успешно импортированы
        }

        ProcessLog::add("Валидных цен для заезда $infoflotCruiseId не найдено");
        return false; // Валидных цен не найдено
    }

    /**
     * Восстановление связей кают с палубами для заезда
     * Использует ТОЧНЫЕ данные из SQLite о палубах для каждой категории кают
     * Если точных данных нет, использует эталонную палубу как fallback
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
            // Проверяем, есть ли уже связь для этой каюты
            $hasLink = DB::table('mcmraak_rivercrs_decks_pivot')
                ->where('cabin_id', $cabinId)
                ->exists();

            if (!$hasLink) {
                // ПРИОРИТЕТ 1: Используем точные данные из SQLite (если есть)
                if (isset($cabinDeckMapping[$cabinId]) && !empty($cabinDeckMapping[$cabinId])) {
                    $deckName = $cabinDeckMapping[$cabinId];
                    $deck = $this->getDeck($deckName);
                    if ($deck) {
                        try {
                            $this->deckPivotCheck($cabinId, $deck->id);
                            $restoredCount++;
                            ProcessLog::add("Восстановлена связь каюты $cabinId с палубой {$deck->id} ({$deckName}) из SQLite");
                        } catch (\Exception $e) {
                            // Игнорируем ошибки дубликатов
                        }
                        continue;
                    }
                }

                // ПРИОРИТЕТ 2: Используем эталонную палубу (fallback)
                if ($referenceDeckId) {
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
                } else {
                    ProcessLog::add("Предупреждение: не удалось создать связь для cabin_id=$cabinId - нет доступных палуб для теплохода $shipId");
                }
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
            // Получаем stream_id по code='Infoflot'
            $stream = Stream::where('code', 'Infoflot')->first();
            
            if (!$stream) {
                // Если stream не найден, просто логируем в ProcessLog
                ProcessLog::add("Не удалось записать ошибку в ErrorLog: stream 'Infoflot' не найден. Ошибка: $errorMessage");
                return;
            }
            
            $errorLog = new ErrorLog;
            $errorLog->stream_id = $stream->id;
            $errorLog->call = 'InfoflotV2@fillInfoflotCruises';
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
        $statePath = storage_path('worker/InfoflotState.yaml');
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
        $statePath = storage_path('worker/InfoflotState.yaml');
        
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

