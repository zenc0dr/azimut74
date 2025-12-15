<?php namespace Zen\Worker\Console\transfer;

use Zen\Worker\Classes\ProcessLog;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use DB;
use Carbon\Carbon;
use PDO;

/**
 * Единый процессор для всех источников
 * Работает с единой структурой SQLite баз
 */
class UnifiedProcessor extends TransferProcessor
{
    /**
     * @var \Illuminate\Console\Command|null Команда для вывода в консоль
     */
    protected $command = null;
    
    /**
     * Установка команды для вывода в консоль
     * 
     * @param \Illuminate\Console\Command $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }
    
    /**
     * Вывод сообщения в консоль (если команда установлена)
     * 
     * @param string $message
     * @param string $type info|line|warn|error
     */
    protected function output($message, $type = 'line')
    {
        if ($this->command) {
            switch ($type) {
                case 'info':
                    $this->command->info($message);
                    break;
                case 'warn':
                    $this->command->warn($message);
                    break;
                case 'error':
                    $this->command->error($message);
                    break;
                default:
                    $this->command->line($message);
            }
        }
        ProcessLog::add($message);
    }
    
    /**
     * Основной метод обработки всех круизов из SQLite
     */
    public function process()
    {
        $this->output("🔄 Обработка заездов {$this->sourceName} из SQLite (UnifiedProcessor)", 'info');
        
        // Импортируем палубы из SQLite в MySQL
        $this->importDecks();
        
        $cruises = $this->db->getAllCruises();
        $totalCruises = count($cruises);
        
        $this->output("📋 Найдено заездов для обработки: $totalCruises", 'info');
        
        $errorsCount = 0;
        $processedCount = 0;
        $skippedCount = 0;
        
        $this->output("⏳ Начинаем обработку...", 'line');
        
        foreach ($cruises as $index => $cruise) {
            $cruiseNum = $index + 1;
            try {
                $cruiseId = $cruise['id'];
                
                // Выводим прогресс каждые 10 круизов или для каждого круиза, если их меньше 20
                if ($totalCruises <= 20 || $cruiseNum % 10 == 0 || $cruiseNum == $totalCruises) {
                    $this->output("  [$cruiseNum/$totalCruises] Обработка круиза {$this->edsCode}:$cruiseId...", 'line');
                }
                
                $checkinId = $this->importCruise($cruise);
                
                if ($checkinId) {
                    $processedCount++;
                    if ($totalCruises <= 20 || $cruiseNum % 10 == 0 || $cruiseNum == $totalCruises) {
                        $this->output("  ✅ Круиз {$this->edsCode}:$cruiseId обработан успешно (ID заезда: $checkinId)", 'line');
                    }
                } else {
                    $skippedCount++;
                    if ($totalCruises <= 20) {
                        $this->output("  ⚠️  Круиз {$this->edsCode}:$cruiseId пропущен", 'warn');
                    }
                }
            } catch (\Exception $e) {
                $errorsCount++;
                $this->output("  ❌ Ошибка обработки круиза {$this->edsCode}:{$cruise['id']}: " . $e->getMessage(), 'error');
            }
        }
        
        $this->output("✅ Обработка всех заездов {$this->sourceName} завершена", 'info');
        $this->output("📊 Результаты: обработано: $processedCount, пропущено: $skippedCount, ошибок: $errorsCount из $totalCruises", 'info');
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
            $this->output("  ⚠️  Теплоход с ID $shipId не найден в SQLite для круиза $cruiseId", 'warn');
            return null;
        }
        
        // Получаем или создаем теплоход в MySQL
        $ship = $this->getMotorship($shipData['name'], $shipId);
        
        // Проверка исключения теплохода (не считается ошибкой, просто пропускаем)
        if (!$ship) {
            $this->output("  ⚠️  Теплоход {$shipData['name']} исключён", 'warn');
            return null;
        }
        
        // Получаем или создаем заезд
        $checkin = $this->getOrCreateCheckin($cruiseId);
        
        // Обработка дат
        $dateStart = null;
        $dateEnd = null;

        // В Waterway встречается формат "YYYY-MM-DD HH:MM:SS HH:MM:SS"
        // (двойное время). Приводим к валидному, иначе Carbon::parse() падает,
        // а ниже это выглядит как "отсутствуют даты".
        $rawDateStart = $cruise['date_start'] ?? null;
        $rawDateEnd = $cruise['date_end'] ?? null;
        $dateStartRaw = $this->cleanDuplicateTime($rawDateStart);
        $dateEndRaw = $this->cleanDuplicateTime($rawDateEnd);
        
        if (!empty($dateStartRaw)) {
            try {
                // Используем master()->carbon() для совместимости
                if (function_exists('master') && method_exists(master(), 'carbon')) {
                    $dateStart = master()->carbon($dateStartRaw)->toDateTimeString();
                } else {
                    $dateStart = Carbon::parse($dateStartRaw)->toDateTimeString();
                }
            } catch (\Exception $e) {
                $this->output(
                    "  ⚠️  Круиз $cruiseId: не удалось распарсить date_start. raw=" .
                    ($rawDateStart ?? 'null') .
                    " cleaned=" .
                    ($dateStartRaw ?? 'null') .
                    " err=" . $e->getMessage(),
                    'warn'
                );
            }
        }
        
        if (!empty($dateEndRaw)) {
            try {
                if (function_exists('master') && method_exists(master(), 'carbon')) {
                    $dateEnd = master()->carbon($dateEndRaw)->toDateTimeString();
                } else {
                    $dateEnd = Carbon::parse($dateEndRaw)->toDateTimeString();
                }
            } catch (\Exception $e) {
                $this->output(
                    "  ⚠️  Круиз $cruiseId: не удалось распарсить date_end. raw=" .
                    ($rawDateEnd ?? 'null') .
                    " cleaned=" .
                    ($dateEndRaw ?? 'null') .
                    " err=" . $e->getMessage(),
                    'warn'
                );
            }
        }
        
        if (!$dateStart || !$dateEnd) {
            $this->output(
                "  ⚠️  Круиз $cruiseId: даты невалидны/не распарсились, заезд пропущен " .
                "(date_start=" . ($rawDateStart ?? 'null') . ", date_end=" . ($rawDateEnd ?? 'null') . ")",
                'warn'
            );
            return null;
        }
        
        // Обработка маршрута
        $waybill = $this->processWaybillData($cruise['waybill_data'] ?? '');
        
        // Если waybill_data пустой или не обработался, пытаемся создать маршрут из поля route или названия
        if (!$waybill || empty($waybill) || count($waybill) < 2) {
            $waybillFromRoute = $this->createWaybillFromRoute($cruise['route'] ?? '', $cruise['name'] ?? '');
            if ($waybillFromRoute && count($waybillFromRoute) >= 2) {
                $waybill = $waybillFromRoute;
            }
        }
        
        // Проверка валидности маршрута
        if (!$waybill || empty($waybill) || count($waybill) < 2) {
            $this->output("  ⚠️  Круиз $cruiseId: отсутствует маршрут, заезд пропущен", 'warn');
            return null;
        }
        
        // Проверяем наличие цен ДО создания заезда
        $prices = $this->db->getPricesByCruiseId($cruiseId);
        if (empty($prices)) {
            $this->output("  ⚠️  Круиз $cruiseId: отсутствуют цены, заезд пропущен", 'warn');
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
        
        // Импорт цен из SQLite
        $pricesImported = $this->importPrices($checkin->id, $cruiseId, $ship->id);
        
        // Цены должны быть, но на всякий случай проверяем
        if (!$pricesImported) {
            $this->output("  ⚠️  Для заезда {$this->edsCode}:$cruiseId не удалось импортировать цены, заезд деактивирован", 'warn');
            $checkin->active = 0;
            $checkin->createCache = false;
            $checkin->save();
        } else {
            // Очищаем кеш и пересоздаём его с правильными данными
            $this->clearCheckinCache($checkin->id);
            $this->rebuildCheckinCache($checkin->id);
        }
        
        return $checkin->id;
    }

    /**
     * Исправляет формат "YYYY-MM-DD HH:MM:SS HH:MM:SS" -> "YYYY-MM-DD HH:MM:SS"
     */
    private function cleanDuplicateTime($dateString)
    {
        if (empty($dateString) || !is_string($dateString)) {
            return $dateString;
        }

        $dateString = trim($dateString);

        if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \d{2}:\d{2}:\d{2}$/', $dateString, $m)) {
            return $m[1];
        }

        return $dateString;
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
        
        $this->output("  💰 Импорт цен для заезда $checkinId: найдено " . count($prices) . " цен", 'line');
        
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
            
            // Проверяем, что категория не в исключениях (getCabinCategoryId возвращает 0 для исключенных)
            if (!$cabinId || $cabinId === 0) {
                continue; // Пропускаем категорию, если она в исключениях
            }
            
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
        $priceKeys = []; // Для проверки дубликатов: ключ = checkin_id:cabin_id

        // ВАЖНО: очищаем палубные цены ДО того, как начнём их записывать через pricePatch().
        // Иначе при очистке в конце мы случайно удаляем только что записанные nprices.
        DB::table('mcmraak_rivercrs_nprices')
            ->where('checkin_id', $checkinId)
            ->delete();
        
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
                // Сохраняем цену с палубой в nprices (если есть информация о палубе)
                // ВАЖНО: Проверяем deck_name из каждой цены, т.к. у категории может быть несколько палуб
                // Делаем это ДО проверки дубликатов, чтобы сохранить все палубы
                $deckName = $price['deck_name'] ?? null;
                $deckId = null;
                
                if ($deckName) {
                    // Получаем палубу по названию (getDeck создаст её, если нет)
                    // ВАЖНО: getDeck() использует LIKE для поиска, что может приводить к неправильным совпадениям
                    // Но для Germes это должно работать, т.к. названия палуб стандартизированы
                    $deck = $this->getDeck($deckName);
                    if ($deck) {
                        $deckId = $deck->id;
                        // Создаем связь каюты с палубой (если ещё не создана)
                        $this->deckPivotCheck($cabinId, $deckId);
                        
                        // Сохраняем в nprices через DeckPricesPatch
                        // ВАЖНО: Сохраняем для каждой цены с палубой, даже если это дубликат по cabin_id
                        // Т.к. одна категория кают может иметь цены на разных палубах
                        if ($this->savePriceWithDeck($checkinId, $cabinId, $deckId, $priceValue, $placesQnt)) {
                            $nprices_count++;
                        } else {
                            // Логируем ошибку сохранения
                            ProcessLog::add("Ошибка сохранения в nprices: checkin_id=$checkinId, cabin_id=$cabinId, deck_id=$deckId, price=$priceValue");
                        }
                    } else {
                        // Логируем, если палуба не найдена
                        ProcessLog::add("Палуба не найдена: deck_name='$deckName' для checkin_id=$checkinId, cabin_id=$cabinId");
                    }
                } else {
                    // Fallback: используем маппинг, если deck_name отсутствует
                    $deckId = $cabinDeckMapping[$cabinId] ?? null;
                    if ($deckId) {
                        // Сохраняем в nprices через DeckPricesPatch
                        if ($this->savePriceWithDeck($checkinId, $cabinId, $deckId, $priceValue, $placesQnt)) {
                            $nprices_count++;
                        }
                    }
                }
                
                // Проверяем на дубликаты: одна категория кают = одна цена на заезд (для pricing)
                $priceKey = $checkinId . ':' . $cabinId;
                if (isset($priceKeys[$priceKey])) {
                    // Дубликат найден - пропускаем или обновляем (берем максимальную цену)
                    $existingIndex = $priceKeys[$priceKey];
                    if ($priceValue > $insert_prices[$existingIndex]['price_a']) {
                        // Обновляем на более высокую цену
                        $insert_prices[$existingIndex]['price_a'] = $priceValue;
                        if ($priceExtra !== null) {
                            $insert_prices[$existingIndex]['price_b'] = $priceExtra;
                        }
                    }
                    continue; // Пропускаем добавление в pricing, но nprices уже сохранен выше
                }
                
                $priceData = [
                    'checkin_id' => $checkinId,
                    'cabin_id' => $cabinId,
                    'price_a' => $priceValue,
                    'price_b' => $priceExtra // Всегда включаем price_b, даже если null
                ];
                
                $insert_prices[] = $priceData;
                $priceKeys[$priceKey] = count($insert_prices) - 1; // Сохраняем индекс для проверки дубликатов
            }
        }
        
        if (!empty($insert_prices)) {
            // Удаляем старые цены и вставляем новые
            DB::table('mcmraak_rivercrs_pricing')
                ->where('checkin_id', $checkinId)
                ->delete();
            
            DB::table('mcmraak_rivercrs_pricing')
                ->insert($insert_prices);
            
            $this->output("  ✅ Цены импортированы: " . count($insert_prices) . " цен в pricing, $nprices_count цен с палубами в nprices", 'line');
            return true; // Цены успешно импортированы
        }
        
        $this->output("  ⚠️  Валидных цен для заезда $cruiseId не найдено", 'warn');
        return false; // Валидных цен не найдено
    }
    
    /**
     * Импорт палуб из SQLite в MySQL
     * Создает палубы в MySQL через getDeck(), если их еще нет
     */
    protected function importDecks()
    {
        try {
            // Получаем все палубы из SQLite
            $pdo = $this->db->getPdo();
            $stmt = $pdo->prepare("SELECT DISTINCT name FROM decks WHERE name IS NOT NULL AND name != '' ORDER BY name");
            $stmt->execute();
            $decks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($decks)) {
                $this->output("  ℹ️  Палубы в SQLite не найдены", 'line');
                return;
            }
            
            $this->output("  🏗️  Импорт палуб из SQLite: найдено " . count($decks) . " палуб", 'line');
            
            $importedCount = 0;
            foreach ($decks as $deck) {
                $deckName = $deck['name'];
                if (empty($deckName)) {
                    continue;
                }
                
                // getDeck() создаст палубу, если её нет, или вернет существующую
                $deckModel = $this->getDeck($deckName);
                if ($deckModel) {
                    $importedCount++;
                    ProcessLog::add("Импортирована палуба: $deckName (ID: {$deckModel->id})");
                }
            }
            
            $this->output("  ✅ Импортировано палуб: $importedCount из " . count($decks), 'line');
        } catch (\Exception $e) {
            $this->output("  ⚠️  Ошибка импорта палуб: " . $e->getMessage(), 'warn');
            ProcessLog::add("Ошибка импорта палуб: " . $e->getMessage());
        }
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
                    // Очищаем название города от HTML тегов и лишних символов
                    $cleanTownName = strip_tags($townName);
                    $cleanTownName = trim($cleanTownName);
                    $cleanTownName = preg_replace('/\s+/', ' ', $cleanTownName);
                    
                    if (!empty($cleanTownName)) {
                        $townId = $this->getTownId($cleanTownName);
                    }
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
        
        // Разбиваем маршрут по разделителям:
        // - " — " (em dash)
        // - " – " (en dash)
        // - " - " (hyphen)
        $routeArray = [];
        if (strpos($routeString, ' — ') !== false) {
            $routeArray = explode(' — ', $routeString);
        } elseif (strpos($routeString, ' – ') !== false) {
            $routeArray = explode(' – ', $routeString);
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

