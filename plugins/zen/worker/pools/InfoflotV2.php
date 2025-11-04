<?php

namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Zen\Worker\Console\infoflot\InfoflotDatabase;
use Zen\Worker\Classes\ProcessLog;
use DB;
use Carbon\Carbon;

class InfoflotV2 extends RiverCrs
{
    public function fillInfoflotCruises()
    {
        ProcessLog::add('Обработка заездов Infoflot из SQLite');

        $db = new InfoflotDatabase();
        $cruises = $db->getAllCruises();

        ProcessLog::add("Найдено заездов для обработки: " . count($cruises));

        foreach ($cruises as $cruise) {
            $infoflot_ship_id = $cruise['infoflot_ship_id'];
            $infoflot_cruise_id = $cruise['id'];
            $infoflot_ship = $db->getShipByInfoflotId($infoflot_ship_id);

            if (!$infoflot_ship) {
                ProcessLog::add("Теплоход с ID $infoflot_ship_id не найден в SQLite");
                continue;
            }

            ProcessLog::add("Обработка заезда infoflot:$infoflot_cruise_id (теплоход: {$infoflot_ship['name']})");

            $ship = $this->getMotorship($infoflot_ship['name'], 'infoflot_id', $infoflot_ship_id);

            // Проверка исключения теплохода
            if (!$ship) {
                ProcessLog::add("Теплоход {$infoflot_ship['name']} исключён");
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
                ProcessLog::add("Ошибка данных! --- cruise_id:$infoflot_cruise_id - Отсутствуют даты, заезд игнорирован.");
                continue;
            }

            // Обработка маршрута
            $waybill = $this->processWaybillData($cruise['route'], $cruise['route_short'] ?? null);

            // Проверка валидности маршрута
            if (!$waybill || empty($waybill) || count($waybill) < 2) {
                ProcessLog::add("Ошибка данных! --- cruise_id:$infoflot_cruise_id - Отсутствует маршрут, заезд игнорирован.");
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
            $checkin->save();

            $this->fixCheckin($checkin->id);

            ProcessLog::add("Заезд добавлен в базу. Обработка цен...");

            // Импорт цен из SQLite
            $pricesImported = $this->importPricesForCruise($checkin->id, $infoflot_cruise_id, $db, $ship->id);

            // Если цены не найдены, деактивируем заезд
            if (!$pricesImported) {
                ProcessLog::add("Для заезда infoflot:$infoflot_cruise_id отсутствуют цены, заезд деактивирован.");
                $checkin->active = 0;
                $checkin->save();
            } else {
                ProcessLog::add("Обработка заезда infoflot:$infoflot_cruise_id завершена.");
            }
        }
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
        $processedDecks = [];

        foreach ($prices as $price) {
            $typeId = $price['type_id'];
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
            /*
            else {
                // Используем название с ID для уникальности
                $categoryName = $categoryName . '|' . $cabinCategoryId;
            }
            */

            $places = $price['places'] ?? 1;

            $cabinId = $this->getCabinCategoryId(
                $categoryName,
                $shipId,
                'infoflot',
                $places
            );

            if ($cabinId) {
                $cabinMapping[$cabinCategoryId] = $cabinId;

                // Работа с палубами
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

            ProcessLog::add("Цены для заезда $infoflotCruiseId: добавлено " . count($insert_prices) . " цен");
            return true; // Цены успешно импортированы
        }

        ProcessLog::add("Валидных цен для заезда $infoflotCruiseId не найдено");
        return false; // Валидных цен не найдено
    }
}

