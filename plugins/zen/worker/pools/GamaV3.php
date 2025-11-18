<?php

namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Zen\Worker\Console\gama\GamaDatabase;
use Zen\Worker\Classes\ProcessLog;
use DB;
use Yaml;

class GamaV3 extends RiverCrs
{
    public function fillGamaCruises()
    {
        ProcessLog::add('Обработка заездов Gama из SQLite');
        
        $db = new GamaDatabase();
        $cruises = $db->getAllCruises();
        $totalCruises = count($cruises);
        
        ProcessLog::add("Найдено заездов для обработки: " . $totalCruises);
        
        // Инициализация файла состояния
        $this->initStateFile($totalCruises);
        
        $errorsCount = 0;
        $processedCount = 0;
        
        foreach ($cruises as $cruise) {
            $gama_ship_id = $cruise['gama_ship_id'];
            $gama_cruise_id = $cruise['id'];
            $gama_ship = $db->getShipByGamaId($gama_ship_id);
            
            ProcessLog::add("Обработка заезда gama:$gama_cruise_id (теплоход: {$gama_ship['name']})");
            
            $ship = $this->getMotorship($gama_ship['name'], 'gama', $gama_ship_id);
            
            // Проверка исключения теплохода (как в GamaV2.php)
            if (!$ship) {
                ProcessLog::add("Теплоход {$gama_ship['name']} исключён");
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }

            $checkin = Checkin::where('eds_code', 'gama')
                ->where('eds_id', $gama_cruise_id)
                ->first();

            if (!$checkin) {
                $checkin = new Checkin;
            }

            $checkin->date = master()->carbon($cruise['date_start'])->toDateTimeString();
            $checkin->dateb = master()->carbon($cruise['date_end'])->toDateTimeString();
            $checkin->desc_1 = $cruise['schedule_html'];
            $checkin->motorship_id = $ship->id;
            $checkin->active = 1;
            $checkin->eds_code = 'gama';
            $checkin->eds_id = $gama_cruise_id;
            // Обработка маршрута
            $waybill = $this->processWaybillData($cruise['waybill_data']);
            
            // Проверка валидности маршрута (как в GamaV2.php)
            if (!$waybill || empty($waybill)) {
                ProcessLog::add("Ошибка данных! --- cruise_id:$gama_cruise_id - Отсутствует маршрут, заезд игнорирован.");
                $errorsCount++;
                $processedCount++;
                $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
                continue;
            }
            
            ProcessLog::add("Маршрут получен");
            
            $checkin->waybill_id = $waybill;
            $checkin->save();

            $this->fixCheckin($checkin->id);

            ProcessLog::add("Заезд добавлен в базу. Обработка цен...");

            // Импорт цен из SQLite
            $pricesImported = $this->importPricesForCruise($checkin->id, $gama_cruise_id, $db, $ship->id);
            
            // Если цены не найдены, деактивируем заезд
            if (!$pricesImported) {
                ProcessLog::add("Для заезда gama:$gama_cruise_id отсутствуют цены, заезд деактивирован.");
                $checkin->active = 0;
                $checkin->save();
            } else {
                // Очищаем кеш, созданный преждевременно в afterSave()
                // и пересоздаём его с правильными данными (цены и связи уже есть)
                $cabox = new \Zen\Cabox\Classes\Cabox('rivercrs');
                $cabox->del('rcrs:' . $checkin->id);
                $cabox->del('exist_array:' . $checkin->id);
                
                // Пересоздаём кеш с правильными данными
                Checkin::getResult($checkin->id, true);
                $checkin->cachePrices();
                
                ProcessLog::add("Кеш для заезда {$checkin->id} обновлён после импорта цен");
                ProcessLog::add("Обработка заезда gama:$gama_cruise_id завершена.");
            }
            
            $processedCount++;
            $this->updateStateFile($processedCount, $totalCruises, $errorsCount, false);
        }
        
        // Финальное обновление состояния
        $this->updateStateFile($processedCount, $totalCruises, $errorsCount, true);
        ProcessLog::add("Обработка всех заездов Gama завершена. Обработано: $processedCount, Ошибок: $errorsCount");
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
                    'town' => $this->getTownId($point['town_name'], 'gama'),
                    'excursion' => '',
                    'bold' => $point['bold'] ?? $point['is_bold'] ?? false
                ];
            }
        }

        return $result;
    }

    /**
     * Импорт цен для заезда из SQLite
     * @return bool true если цены найдены и импортированы, false если цен нет
     */
    private function importPricesForCruise($checkinId, $gamaCruiseId, $db, $shipId)
    {
        // Получаем цены из SQLite
        $prices = $db->getPricesByCruiseId($gamaCruiseId);
        
        if (empty($prices)) {
            return false; // Цен нет
        }

        // Создаем маппинг категорий кают и обрабатываем палубы
        $cabinMapping = [];
        $processedDecks = [];
        
        foreach ($prices as $price) {
            $cabinCategoryId = $price['cabin_category_id'];
            
            // Если уже обработали эту категорию, пропускаем
            if (isset($cabinMapping[$cabinCategoryId])) {
                continue;
            }
            
            // Получаем название категории (без ID, как в InfoflotV2, VolgaV2, GermesV2)
            $categoryName = $price['category_name'] ?? '';
            
            // Если название пустое, используем только ID
            if (empty($categoryName)) {
                ProcessLog::add("Предупреждение: для категории $cabinCategoryId отсутствует название, используем только ID");
                $categoryName = $cabinCategoryId;
            }
            
            $cabinId = $this->getCabinCategoryId(
                $categoryName,
                $shipId,
                'gama',
                $price['places']
            );
            
            if ($cabinId) {
                $cabinMapping[$cabinCategoryId] = $cabinId;
                
                // Работа с палубами (как в GamaV2.php)
                if (isset($price['deck_name']) && !isset($processedDecks[$cabinId])) {
                    $deck = $this->getDeck($price['deck_name']);
                    $this->deckPivotCheck($cabinId, $deck->id);
                    $processedDecks[$cabinId] = true;
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
                $insert_prices[] = [
                    'checkin_id' => $checkinId,
                    'cabin_id' => $cabinId,
                    'price_a' => $price['price_a']
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
            
            ProcessLog::add("Цены для заезда $gamaCruiseId: добавлено " . count($insert_prices) . " цен");
            return true; // Цены успешно импортированы
        }
        
        ProcessLog::add("Валидных цен для заезда $gamaCruiseId не найдено");
        return false; // Валидных цен не найдено
    }

    /**
     * Инициализация файла состояния
     */
    private function initStateFile($totalCruises)
    {
        $statePath = storage_path('worker/GamaState.yaml');
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
        $statePath = storage_path('worker/GamaState.yaml');
        
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
