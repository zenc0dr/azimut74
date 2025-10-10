# Gama Parser Console Commands

Консольный парсер для импорта круизов Gama (Фаза 1 - парсинг в SQLite).

## Архитектура

### Процесс:
- **Фаза 1**: Парсинг данных из API Gama → сохранение в SQLite
- **Фаза 2**: Импорт данных из SQLite → основная БД MySQL (выполняется через Zen\Worker с пулом GamaV3)

### Правильная схема SQLite:
- `ships.id` = `gama_ship_id` (идентификатор теплохода в источнике)
- `cruises.id` = `gama_cruise_id` (идентификатор заезда в источнике)
- `cruises.ship_id` = `ships.id` (связь с теплоходом)
- `cabin_categories.id` = `gama_category_id` (идентификатор категории кают)
- `prices.price_a` и `prices.price_b` (вместо price_1 и price_2)

## Использование

### Парсинг данных в SQLite (Фаза 1):
```bash
# Полный парсинг с очисткой данных
docker exec azimut74 php app/artisan worker:gama-parse --clear

# С ограничением количества записей для тестирования
docker exec azimut74 php app/artisan worker:gama-parse --clear --limit=10
```

### Импорт в MySQL (Фаза 2):
```bash
# Используйте Zen\Worker с пулом GamaV3
# Настройте поток в админке Zen\Worker
```

## Особенности реализации

### Фаза 1 (консольный парсер):
1. **Скачивание данных** из API Gama
2. **Сохранение в SQLite** с правильной схемой
3. **Очистка круизов без цен** в конце фазы 1

### Фаза 2 (Zen\Worker с пулом GamaV3):
1. **Последовательная обработка** заездов (один за раз)
2. **Полная валидация** каждого заезда
3. **Остановка при ошибке** с детальным логированием
4. **Использование методов из RiverCrs** как в GamaV2.php

## Структура файлов

- `GamaParse.php` - основная консольная команда (фаза 1)
- `GamaApiClient.php` - работа с API Gama
- `GamaDataProcessor.php` - обработка данных (фаза 1)
- `GamaDatabase.php` - работа с SQLite
- `GamaV3.php` - пул для Zen\Worker (фаза 2)

## Проверка результатов

### SQLite (после фазы 1):
```bash
# Подключение к SQLite
sqlite3 /aum/projects/azimut74/ocms/plugins/zen/worker/console/gama/gama_data.sqlite

# Проверка данных
SELECT COUNT(*) FROM ships;
SELECT COUNT(*) FROM cruises;
SELECT COUNT(*) FROM prices;
SELECT COUNT(*) FROM cabin_categories;
```

### MySQL (после фазы 2 через Zen\Worker):
```bash
# В tinker
docker exec azimut74 php app/artisan tinker

# Проверка импортированных данных
Mcmraak\Rivercrs\Models\Checkins::where('eds_code', 'gama')->count()
Mcmraak\Rivercrs\Models\Pricing::whereHas('checkin', function($q) { $q->where('eds_code', 'gama'); })->count()
```

## Логирование

Все операции логируются через `ProcessLog::add()`. Логи можно найти в стандартном месте Laravel.

## Обработка ошибок

- **Фаза 1**: Ошибки логируются, но не останавливают процесс
- **Фаза 2 (Zen\Worker)**: При любой ошибке процесс останавливается с детальным описанием

## Кеширование

API запросы кешируются на 6 часов для оптимизации производительности.