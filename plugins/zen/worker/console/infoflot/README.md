# Infoflot Parser Console Commands

Консольный парсер для импорта круизов Infoflot (Фаза 1 - парсинг в SQLite).

## Архитектура

### Процесс:

- **Фаза 1**: Парсинг данных из API Infoflot → сохранение в SQLite
- **Фаза 2**: Импорт данных из SQLite → основная БД MySQL (выполняется через Zen\Worker с пулом InfoflotV2)

### Правильная схема SQLite:

- `ships.id` = `infoflot_ship_id` (идентификатор судна в источнике)
- `cruises.id` = `infoflot_cruise_id` (идентификатор заезда в источнике)
- `cruises.ship_id` = `ships.id` (связь с судном)
- `cabin_categories.id` = `infoflot_type_id` (идентификатор категории кают)
- `prices.price_adult` (цена взрослого билета)

## Использование

### Парсинг данных в SQLite (Фаза 1):

```bash
# Полный парсинг с очисткой данных
docker exec azimut74 php app/artisan worker:infoflot-parse --clear --api-key=b5262f5d8de5be65b201bb5e3f5e544a245b6082

# С ограничением количества записей для тестирования
docker exec azimut74 php app/artisan worker:infoflot-parse --clear --limit=10 --api-key=b5262f5d8de5be65b201bb5e3f5e544a245b6082
```

### Импорт в MySQL (Фаза 2):

```bash
# Используйте Zen\Worker с пулом InfoflotV2
# Настройте поток в админке Zen\Worker
```

## Особенности реализации

### Фаза 1 (консольный парсер):

1. **Получение списка судов** из API Infoflot
2. **Получение круизов** для каждого судна
3. **Получение цен кают** для каждого круиза
4. **Сохранение в SQLite** с правильной схемой
5. **Очистка круизов без цен** в конце фазы 1

### Фаза 2 (Zen\Worker с пулом InfoflotV2):

1. **Последовательная обработка** заездов (один за раз)
2. **Полная валидация** каждого заезда
3. **Остановка при ошибке** с детальным логированием
4. **Использование методов из RiverCrs** как в Infoflot.php

## Структура файлов

- `InfoflotParse.php` - основная консольная команда (фаза 1)
- `InfoflotApiClient.php` - работа с API Infoflot
- `InfoflotDataProcessor.php` - обработка данных (фаза 1)
- `InfoflotDatabase.php` - работа с SQLite
- `InfoflotV2.php` - пул для Zen\Worker (фаза 2)

## API Infoflot

### Эндпоинты:

- `/ships` - список судов
- `/cruises` - список круизов (с фильтром по судну)
- `/cruises/{id}/cabins` - цены кают для круиза

### API ключ:

`b5262f5d8de5be65b201bb5e3f5e544a245b6082`

## Проверка результатов

### SQLite (после фазы 1):

```bash
# Подключение к SQLite
sqlite3 /aum/projects/azimut74/ocms/plugins/zen/worker/console/infoflot/infoflot_data.sqlite

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
Mcmraak\Rivercrs\Models\Checkins::where('eds_code', 'infoflot')->count()
Mcmraak\Rivercrs\Models\Pricing::whereHas('checkin', function($q) { $q->where('eds_code', 'infoflot'); })->count()
```

## Логирование

Все операции логируются через `ProcessLog::add()`. Логи можно найти в стандартном месте Laravel.

## Обработка ошибок

- **Фаза 1**: Ошибки логируются, но не останавливают процесс
- **Фаза 2 (Zen\Worker)**: При любой ошибке процесс останавливается с детальным описанием

## Кеширование

API запросы кешируются на 6 часов для оптимизации производительности.
