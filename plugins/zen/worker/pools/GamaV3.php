<?php

namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Zen\Worker\Console\gama\GamaDatabase;
use Zen\Worker\Classes\ProcessLog;
use DB;

class GamaV3 extends RiverCrs
{
    public function fillGamaCruises()
    {
        ProcessLog::add('Обработка заездов Gama из SQLite');
        
        $db = new GamaDatabase();
        $cruises = $db->getAllCruises();
        
        ProcessLog::add("Найдено заездов для обработки: " . count($cruises));
        
        foreach ($cruises as $cruise) {
            $gama_ship_id = $cruise['gama_ship_id'];
            $gama_cruise_id = $cruise['id'];
            $gama_ship = $db->getShipByGamaId($gama_ship_id);
            
            ProcessLog::add("Обработка заезда gama:$gama_cruise_id (теплоход: {$gama_ship['name']})");
            
            $ship = $this->getMotorship($gama_ship['name'], 'gama', $gama_ship_id);
            
            // Проверка исключения теплохода (как в GamaV2.php)
            if (!$ship) {
                ProcessLog::add("Теплоход {$gama_ship['name']} исключён");
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
                ProcessLog::add("Обработка заезда gama:$gama_cruise_id завершена.");
            }

        }
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
                    'bold' => $point['is_bold'] ?? false
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
            
            $cabinId = $this->getCabinCategoryId(
                $price['category_name'] . '|' . $price['cabin_category_id'],
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
}
