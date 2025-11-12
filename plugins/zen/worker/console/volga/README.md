# Volga Parser Console Commands

Консольный парсер для импорта круизов Volga (Фаза 1 - парсинг в SQLite).

## Архитектура

### Процесс:

- **Фаза 1**: Парсинг данных из XML файла Volga → сохранение в SQLite
- **Фаза 2**: Импорт данных из SQLite → основная БД MySQL (выполняется через Zen\Worker с пулом VolgaV2)

### Правильная схема SQLite:

- `ships.id` = `volga_ship_id` (идентификатор теплохода в источнике)
- `cruises.id` = `volga_cruise_id` (идентификатор заезда в источнике)
- `cruises.ship_id` = `ships.id` (связь с теплоходом)
- `cabin_categories.id` = `volga_class_id` (идентификатор категории кают)
- `prices.price_value` и `prices.price2_value` (цена и специальное предложение SPO)

## Использование

### Парсинг данных в SQLite (Фаза 1):

```bash
# Полный парсинг с очисткой данных
docker exec azimut74 php app/artisan worker:volga-parse --clear

# С ограничением количества записей для тестирования
docker exec azimut74 php app/artisan worker:volga-parse --clear --limit=10

# С указанием URL источника данных
docker exec azimut74 php app/artisan worker:volga-parse --clear --next-url=http://test.volgawolga.ru/xml/daily2024.xml
```

### Импорт в MySQL (Фаза 2):

```bash
# Используйте Zen\Worker с пулом VolgaV2
# Настройте поток в админке Zen\Worker
```

## Особенности реализации

### Фаза 1 (консольный парсер):

1. **Скачивание XML файла** из источника (по умолчанию `http://test.volgawolga.ru/xml/daily2024.xml`)
2. **Парсинг XML** через `Convertor::xmlToArr()`
3. **Сохранение в SQLite** с правильной схемой:
   - Теплоходы (ships)
   - Палубы (decks)
   - Категории кают (cabin_categories)
   - Каюты (cabins) - связь между категориями и палубами
   - Круизы (cruises) с путевыми листами
   - Цены (prices) с поддержкой SPO
4. **Очистка круизов без цен** в конце фазы 1

### Фаза 2 (Zen\Worker с пулом VolgaV2):

1. **Последовательная обработка** заездов (один за раз)
2. **Полная валидация** каждого заезда
3. **Остановка при ошибке** с детальным логированием
4. **Использование методов из RiverCrs** как в VolgaCruises.php

## Структура файлов

- `VolgaParse.php` - основная консольная команда (фаза 1)
- `VolgaApiClient.php` - скачивание и парсинг XML файлов
- `VolgaDataProcessor.php` - обработка данных (фаза 1)
- `VolgaDatabase.php` - работа с SQLite
- `VolgaV2.php` - пул для Zen\Worker (фаза 2) - будет создан позже

## Источник данных

### URL по умолчанию:

`http://test.volgawolga.ru/xml/daily2024.xml`

### Структура XML:

XML файл содержит следующие секции:
- `<ships>` - список теплоходов
- `<classes>` - категории кают
- `<decks>` - палубы
- `<cabins>` - каюты (связь class_id и deck_id)
- `<cruises>` - круизы с маршрутами
- `<prices>` - цены на каюты
- `<spos>` - специальные предложения (SPO)

## Проверка результатов

### SQLite (после фазы 1):

```bash
# Подключение к SQLite
sqlite3 /aum/projects/azimut74/ocms/plugins/zen/worker/console/volga/volga_data.sqlite

# Проверка данных
SELECT COUNT(*) FROM ships;
SELECT COUNT(*) FROM cruises;
SELECT COUNT(*) FROM prices;
SELECT COUNT(*) FROM cabin_categories;
SELECT COUNT(*) FROM waybills;
```

### MySQL (после фазы 2 через Zen\Worker):

```bash
# В tinker
docker exec azimut74 php app/artisan tinker

# Проверка импортированных данных
Mcmraak\Rivercrs\Models\Checkins::where('eds_code', 'volga')->count()
Mcmraak\Rivercrs\Models\Pricing::whereHas('checkin', function($q) { $q->where('eds_code', 'volga'); })->count()
```

## Логирование

Все операции логируются через `ProcessLog::add()`. Логи можно найти в стандартном месте Laravel.

## Обработка ошибок

- **Фаза 1**: Ошибки логируются, но не останавливают процесс (кроме критических)
- **Фаза 2 (Zen\Worker)**: При любой ошибке процесс останавливается с детальным описанием

## Особенности данных Volga

### Путевые листы:

- Формируются из поля `route` круиза
- Разделитель: ` - ` (дефис с пробелами)
- Используется метод `volgaWay()` для преобразования маршрута в путевой лист
- Сохраняются в таблице `waybills` с привязкой к круизу

### Цены:

- Основная цена хранится в `price_value`
- Специальные предложения (SPO) хранятся в `price2_value`
- Цены с `nofull=1` не сохраняются (как в оригинале)
- Составной ключ `cruise_id:class_id` используется для связи с SPO

### Категории кают:

- Связь с палубами через таблицу `cabins`
- Поля `places_main_count` и `places_extra_count` для количества мест
- Поле `no_full` указывает на возможность неполной загрузки

## Опции команды

- `--clear` или `-c` - Очистить существующие данные перед парсингом
- `--limit` или `-l` - Ограничить количество записей для тестирования
- `--timeout` или `-t` - Таймаут для HTTP запросов в секундах (по умолчанию 30)
- `--next-url` или `-u` - URL источника XML данных (по умолчанию `http://test.volgawolga.ru/xml/daily2024.xml`)

