<?php namespace Zen\Worker\Console\waterway;

use Illuminate\Console\Command;
use Zen\Worker\Console\waterway\WaterwayApiClient;
use Zen\Worker\Console\waterway\WaterwayCache;
use Exception;

class WaterwayCheckRoomClass extends Command
{
    protected $name = 'worker:waterway-check-roomclass';
    protected $description = 'Проверка структуры roomClass в ответе API Waterway на наличие ID';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $this->info('Проверка структуры roomClass в API Waterway...');
        
        try {
            $apiClient = new WaterwayApiClient();

            // Получаем список круизов
            $this->info('Получение списка круизов...');
            $cruises = $apiClient->getCruises();

            if (empty($cruises)) {
                $this->error('❌ Не удалось получить список круизов');
                return 1;
            }

            // Берем первый круиз для проверки
            $firstCruiseId = array_key_first($cruises);
            $this->info("Проверяем структуру roomClass для круиза ID: $firstCruiseId");
            $this->line('');

            // Получаем цены круиза
            $this->info('Получение данных о ценах...');
            $pricesData = $apiClient->getCruisePrices($firstCruiseId);

            if (!$pricesData || !isset($pricesData['tariffs'])) {
                $this->error('❌ Не удалось получить данные о ценах');
                return 1;
            }

            // Получаем сырой ответ из кеша для анализа структуры
            $cache = new WaterwayCache();
            $cacheKey = "waterway_prices_{$firstCruiseId}";
            $rawResponse = $cache->get($cacheKey);

            if (!$rawResponse) {
                $this->error('❌ Не удалось получить сырой ответ из кеша');
                return 1;
            }

            $this->info('=== АНАЛИЗ СТРУКТУРЫ roomClass ===');
            $this->line('');

            // Проверяем структуру decks -> roomClasses
            if (isset($rawResponse['result']['decks'])) {
                $foundRoomClass = false;
                $foundId = false;
                
                foreach ($rawResponse['result']['decks'] as $deckIndex => $deck) {
                    // Проверяем структуру deck
                    $this->line("=== ПАЛУБА #$deckIndex ===");
                    $this->line('Все поля deck:');
                    $deckKeys = array_keys($deck);
                    foreach ($deckKeys as $key) {
                        $value = $deck[$key];
                        $type = gettype($value);
                        
                        if (is_array($value)) {
                            $preview = count($value) . " элементов";
                            if (count($value) > 0 && isset($value[0])) {
                                $firstItem = is_array($value[0]) ? 'массив' : (is_string($value[0]) && mb_strlen($value[0]) > 30 ? mb_substr($value[0], 0, 30) . '...' : $value[0]);
                                $preview .= " (первый: " . json_encode($firstItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ")";
                            }
                        } elseif (is_string($value) && mb_strlen($value) > 50) {
                            $preview = mb_substr($value, 0, 50) . "...";
                        } else {
                            $preview = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                        
                        $this->line("  - $key ($type): $preview");
                    }
                    $this->line('');
                    
                    if (!isset($deck['roomClasses']) || !is_array($deck['roomClasses'])) {
                        continue;
                    }
                    
                    foreach ($deck['roomClasses'] as $roomClassIndex => $roomClass) {
                        $foundRoomClass = true;
                        
                        $this->line("--- roomClass #$roomClassIndex на палубе #$deckIndex ---");
                        $this->line('Все поля roomClass:');
                        
                        // Выводим все ключи
                        $keys = array_keys($roomClass);
                        foreach ($keys as $key) {
                            $value = $roomClass[$key];
                            $type = gettype($value);
                            
                            if (is_array($value)) {
                                $preview = count($value) . " элементов";
                                if (count($value) > 0 && isset($value[0])) {
                                    $firstItem = is_array($value[0]) ? 'массив' : (is_string($value[0]) && mb_strlen($value[0]) > 30 ? mb_substr($value[0], 0, 30) . '...' : $value[0]);
                                    $preview .= " (первый: " . json_encode($firstItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ")";
                                }
                            } elseif (is_string($value) && mb_strlen($value) > 50) {
                                $preview = mb_substr($value, 0, 50) . "...";
                            } else {
                                $preview = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            }
                            
                            $this->line("  - $key ($type): $preview");
                        }
                        
                        // Проверяем наличие ID
                        if (isset($roomClass['id'])) {
                            $this->info("\n✅ НАЙДЕНО: roomClass['id'] = " . $roomClass['id']);
                            $foundId = true;
                        } else {
                            $this->warn("\n❌ НЕ НАЙДЕНО: roomClass['id'] отсутствует");
                        }
                        
                        // Проверяем другие возможные варианты ID
                        $possibleIdFields = ['roomClassId', 'classId', 'categoryId', 'room_class_id', 'class_id', 'category_id'];
                        foreach ($possibleIdFields as $field) {
                            if (isset($roomClass[$field])) {
                                $this->warn("⚠️  Найдено альтернативное поле: roomClass['$field'] = " . $roomClass[$field]);
                                $foundId = true;
                            }
                        }
                        
                        $this->line('');
                        
                        // Проверяем только первый roomClass для краткости
                        break 2;
                    }
                }
                
                if (!$foundRoomClass) {
                    $this->error('❌ Не найдено ни одного roomClass в ответе');
                    return 1;
                }
            } else {
                $this->error("❌ Структура ответа не содержит 'result.decks'");
                $this->line('Доступные ключи в ответе:');
                $this->line(implode(', ', array_keys($rawResponse)));
                return 1;
            }

            $this->line('');
            $this->info('=== РЕКОМЕНДАЦИЯ ===');
            if ($foundId) {
                $this->info('✅ ID найден! Можно добавить поле waterway_id в таблицу mcmraak_rivercrs_cabins');
                return 0;
            } else {
                $this->warn('❌ ID не найден. Использовать только waterway_name (название категории)');
                return 0;
            }
            
        } catch (Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

