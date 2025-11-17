# Waterway Parser Console Commands

Консольный парсер для импорта круизов Waterway (Фаза 1 - парсинг в SQLite).

## Архитектура

### Процесс:

- **Фаза 1**: Парсинг данных из API Waterway → сохранение в SQLite
- **Фаза 2**: Импорт данных из SQLite → основная БД MySQL (выполняется через Zen\Worker с пулом WaterwayV2)

### Правильная схема SQLite:

- `ships.id` = `waterway_ship_id` (идентификатор теплохода в источнике)
- `cruises.id` = `waterway_cruise_id` (идентификатор заезда в источнике)
- `cruises.ship_id` = `ships.id` (связь с теплоходом)
- `prices.cabin_category_name` (название категории кают из rt_name)
- `prices.price_value` (цена взрослого билета)

## Использование

### Парсинг данных в SQLite (Фаза 1):

```bash
# Полный парсинг с очисткой данных
docker exec azimut74 php app/artisan worker:waterway-parse --clear

# С ограничением количества записей для тестирования
docker exec azimut74 php app/artisan worker:waterway-parse --clear --limit=10

# Парсинг с очисткой кеша
docker exec azimut74 php app/artisan worker:waterway-parse --clear_cache --clear
```

### Импорт в MySQL (Фаза 2):

```bash
# Используйте Zen\Worker с пулом WaterwayV2
# Настройте поток в админке Zen\Worker
```

## Особенности реализации

### Фаза 1 (консольный парсер):

1. **Получение списка теплоходов** из API Waterway
2. **Получение списка круизов** из API Waterway
3. **Для каждого круиза:**
   - Получение расписания (cruise-days.php) - опционально
   - Получение цен кают (cruise-prices.php)
   - Формирование HTML расписания (если расписание получено)
   - Формирование waybill (из расписания или из названия круиза)
   - Сохранение в SQLite
4. **Очистка круизов без цен** в конце фазы 1

### Фаза 2 (Zen\Worker с пулом WaterwayV2):

1. **Последовательная обработка** заездов (один за раз)
2. **Полная валидация** каждого заезда
3. **Остановка при ошибке** с детальным логированием
4. **Использование методов из RiverCrs** как в Waterway.php

## Структура файлов

- `WaterwayParse.php` - основная консольная команда (фаза 1)
- `WaterwayApiClient.php` - работа с API Waterway
- `WaterwayDataProcessor.php` - обработка данных и формирование расписания (фаза 1)
- `WaterwayDatabase.php` - работа с SQLite
- `WaterwayCache.php` - файловый кеш для API ответов
- `WaterwayV2.php` - пул для Zen\Worker (фаза 2) - будет создан позже

## API Waterway

### Эндпоинты:

- `/motorships.php` - список теплоходов
- `/cruises.php` - список круизов
- `/cruise-prices.php?cruise={id}` - цены кают для круиза
- `/cruise-days.php?cruise={id}` - расписание круиза по дням

### API ключ (pauth):

`kefhjkdRgwFdkVHpRHGs`

## Формирование расписания

Парсер автоматически формирует HTML расписание для каждого круиза:

- Если расписание получено через API (`cruise-days.php`):
  - Формируется HTML таблица с колонками: "День", "Стоянка", "Программа дня"
  - Сохраняется в поле `schedule_html` (потом переносится в `desc_1` без изменений)
  - Формируется waybill из расписания с экскурсиями
  - Обрабатываются точные даты из расписания

- Если расписание не получено:
  - `schedule_html` = пустая строка
  - Формируется минимальный waybill из поля `name` круиза
  - Используются базовые даты из `dateStart` и `dateStop`

## Проверка результатов

### SQLite (после фазы 1):

```bash
# Подключение к SQLite
sqlite3 /aum/projects/azimut74/ocms/plugins/zen/worker/console/waterway/waterway_data.sqlite

# Проверка данных
SELECT COUNT(*) FROM ships;
SELECT COUNT(*) FROM cruises;
SELECT COUNT(*) FROM prices;

# Проверка расписаний
SELECT id, name, CASE WHEN schedule_html = '' THEN 'Нет расписания' ELSE 'Есть расписание' END as has_schedule FROM cruises LIMIT 10;
```

### MySQL (после фазы 2 через Zen\Worker):

```bash
# В tinker
docker exec azimut74 php app/artisan tinker

# Проверка импортированных данных
Mcmraak\Rivercrs\Models\Checkins::where('eds_code', 'waterway')->count()
Mcmraak\Rivercrs\Models\Pricing::whereHas('checkin', function($q) { $q->where('eds_code', 'waterway'); })->count()
```

## Логирование

Все операции логируются через `ProcessLog::add()`. Логи можно найти в стандартном месте Laravel.

## Обработка ошибок

- **Фаза 1**: Ошибки логируются, но не останавливают процесс (кроме критических)
- **Фаза 2 (Zen\Worker)**: При любой ошибке процесс останавливается с детальным описанием

## Кеширование

API запросы кешируются вечно в файловом кеше (`storage/waterway_cache/`). Кеш удаляется только при явном вызове `--clear_cache`.

## Особенности данных Waterway

### Расписание:

- Получается через API `cruise-days.php`
- Формируется HTML таблица через метод `wwGraph()`
- Сохраняется в поле `schedule_html` для последующего переноса в `desc_1`

### Цены:

- Обрабатываются только для тарифа "Тариф Взрослый"
- Сохраняются с названием категории кают (`rt_name`), описанием (`rp_name`), палубой (`deck_name`) и ценой (`price_value`)

### Waybill:

- Формируется из расписания (с экскурсиями) или из названия круиза (минимальный)
- Сохраняется в поле `waybill_data` как JSON массив

## Опции команды

- `--clear` или `-c` - Очистить существующие данные перед парсингом
- `--clear_cache` - Очистить кеш API перед парсингом
- `--limit` или `-l` - Ограничить количество записей для тестирования
- `--timeout` или `-t` - Таймаут для HTTP запросов в секундах (по умолчанию 30)

