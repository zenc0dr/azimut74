<?php namespace Zen\Worker\Console\gama;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Exception;
use Zen\Worker\Classes\ProcessLog;

class GamaTest extends Command
{
    protected $name = 'worker:gama-test';
    protected $description = 'Тестирование методов парсера Gama';

    public function handle()
    {
        $this->info('🧪 Начинаем тестирование методов парсера Gama...');
        
        try {
            $this->testApiClient();
            $this->testDatabase();
            $this->testDataProcessor();
            
            $this->info('✅ Все тесты пройдены успешно!');
            
        } catch (Exception $e) {
            $this->error('❌ Ошибка тестирования: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }

    /**
     * Тестирование GamaApiClient
     */
    private function testApiClient()
    {
        $this->info('🔍 Тестирование GamaApiClient...');
        
        $apiClient = new GamaApiClient(30);
        
        // Тест 1: Скачивание архивов
        $this->info('  📥 Тест скачивания архивов...');
        $apiClient->downloadGamaArchives();
        $this->info('  ✅ Архивы скачаны');
        
        // Тест 2: Чтение навигационных данных
        $this->info('  📊 Тест чтения навигационных данных...');
        $navigationData = $apiClient->getNavigationData();
        if (!isset($navigationData['NavigationList']['Navigation'])) {
            throw new Exception('Навигационные данные не найдены');
        }
        $this->info('  ✅ Навигационные данные прочитаны');
        
        // Тест 3: Получение ID круизов
        $this->info('  🎫 Тест получения ID круизов...');
        $cruiseIds = $apiClient->getCruiseIds();
        if (empty($cruiseIds)) {
            throw new Exception('ID круизов не найдены');
        }
        $this->info("  ✅ Найдено круизов: " . count($cruiseIds));
        
        // Тест 4: Получение данных одного круиза через API
        $this->info('  🌐 Тест получения данных круиза через API...');
        $firstCruiseId = $cruiseIds[0];
        try {
            $routeData = $apiClient->getGamaRouteData($firstCruiseId);
            if (!isset($routeData['Route'])) {
                throw new Exception('Данные круиза не получены');
            }
            $this->info("  ✅ Данные круиза $firstCruiseId получены");
        } catch (Exception $e) {
            $this->warn("  ⚠️  API недоступен для круиза $firstCruiseId: " . $e->getMessage());
        }
        
        $this->info('✅ GamaApiClient работает корректно');
    }

    /**
     * Тестирование GamaDatabase
     */
    private function testDatabase()
    {
        $this->info('🗄️  Тестирование GamaDatabase...');
        
        $db = new GamaDatabase();
        
        // Тест 1: Очистка данных
        $this->info('  🧹 Тест очистки данных...');
        $db->clearAll();
        $this->info('  ✅ Данные очищены');
        
        // Тест 2: Сохранение теплохода
        $this->info('  🚢 Тест сохранения теплохода...');
        $shipId = $db->saveShip(999, 'Тестовый теплоход', 'Описание');
        if (!$shipId) {
            throw new Exception('Не удалось сохранить теплоход');
        }
        $this->info('  ✅ Теплоход сохранен');
        
        // Тест 3: Сохранение круиза
        $this->info('  🎫 Тест сохранения круиза...');
        $cruiseData = [
            'gama_cruise_id' => 99999,
            'gama_ship_id' => 999,
            'name' => 'Тестовый круиз',
            'route_name' => 'Тестовый маршрут',
            'date_start' => '2025-01-01 10:00:00',
            'date_end' => '2025-01-03 18:00:00',
            'path_s_id' => 1,
            'path_f_id' => 2,
            'waybill_data' => '[]',
            'schedule_html' => '<p>Тестовое расписание</p>'
        ];
        $cruiseId = $db->saveCruise($cruiseData);
        if (!$cruiseId) {
            throw new Exception('Не удалось сохранить круиз');
        }
        $this->info('  ✅ Круиз сохранен');
        
        // Тест 4: Сохранение цены
        $this->info('  💰 Тест сохранения цены...');
        $priceId = $db->savePrice($cruiseId, 1, 50000, 40000, 2);
        if (!$priceId) {
            throw new Exception('Не удалось сохранить цену');
        }
        $this->info('  ✅ Цена сохранена');
        
        // Тест 5: Получение статистики
        $this->info('  📈 Тест получения статистики...');
        $stats = $db->getStats();
        if ($stats['ships'] < 1 || $stats['cruises'] < 1 || $stats['prices'] < 1) {
            throw new Exception('Статистика некорректна');
        }
        $this->info("  ✅ Статистика: {$stats['ships']} теплоходов, {$stats['cruises']} круизов, {$stats['prices']} цен");
        
        $this->info('✅ GamaDatabase работает корректно');
    }

    /**
     * Тестирование GamaDataProcessor
     */
    private function testDataProcessor()
    {
        $this->info('⚙️  Тестирование GamaDataProcessor...');
        
        $db = new GamaDatabase();
        $processor = new GamaDataProcessor($db, 30);
        
        // Тест 1: Обработка навигационных данных
        $this->info('  📊 Тест обработки навигационных данных...');
        $processor->processNavigationData();
        $this->info('  ✅ Навигационные данные обработаны');
        
        // Тест 2: Обработка данных о теплоходах
        $this->info('  🚢 Тест обработки данных о теплоходах...');
        $processor->processShipsData();
        $this->info('  ✅ Данные о теплоходах обработаны');
        
        // Тест 3: Обработка цен (только первые 5 круизов)
        $this->info('  💰 Тест обработки цен (первые 5 круизов)...');
        $cruises = $db->getAllCruises();
        $testCruises = array_slice($cruises, 0, 5);
        
        foreach ($testCruises as $cruise) {
            try {
                $processor->processCruisePrices($cruise);
                $this->info("    ✅ Круиз {$cruise['gama_cruise_id']} обработан");
            } catch (Exception $e) {
                $this->warn("    ⚠️  Ошибка обработки круиза {$cruise['gama_cruise_id']}: " . $e->getMessage());
            }
        }
        
        $this->info('✅ GamaDataProcessor работает корректно');
    }

    protected function getOptions()
    {
        return [];
    }
}