# Конфигурация путей к базам данных SQLite

## Описание

Класс `TransferConfig` позволяет настраивать пути к базам данных SQLite для каждого источника через переменные окружения в основном `.env` файле проекта.

## Использование

### Пути по умолчанию

По умолчанию используются следующие пути (относительно `base_path()`):

- `waterway` → `parsers/db/waterway_data.sqlite`
- `gama` → `parsers/db/gama_data.sqlite`
- `germes` → `parsers/db/germes_data.sqlite`
- `infoflot` → `parsers/db/infoflot_data.sqlite`
- `volga` → `parsers/db/volga_data.sqlite`

### Переопределение путей через .env

Чтобы изменить путь к базе данных для конкретного источника, добавьте в основной `.env` файл проекта (в корне `ocms/`) следующие переменные:

```env
# Пути к базам данных SQLite парсеров
WATERWAY_PATH=storage/parsers_db/waterway_data.sqlite
GAMA_PATH=storage/parsers_db/gama_data.sqlite
GERMES_PATH=storage/parsers_db/germes_data.sqlite
INFOFLOT_PATH=storage/parsers_db/infoflot_data.sqlite
VOLGA_PATH=storage/parsers_db/volga_data.sqlite
```

**Важно:** Пути указываются относительно `base_path()`, то есть относительно корня проекта `ocms/`.

### Примеры

**Пример 1:** Использование путей по умолчанию (ничего не нужно добавлять в `.env`)

```php
$dbPath = TransferConfig::getDbPath('gama');
// Результат: /path/to/ocms/parsers/db/gama_data.sqlite
```

**Пример 2:** Переопределение пути через `.env`

В `.env`:
```env
GAMA_PATH=storage/custom/gama.sqlite
```

В коде:
```php
$dbPath = TransferConfig::getDbPath('gama');
// Результат: /path/to/ocms/storage/custom/gama.sqlite
```

## API

### `TransferConfig::getDbPath($source)`

Получить абсолютный путь к базе данных для источника.

**Параметры:**
- `$source` (string) - Имя источника: `waterway`, `gama`, `germes`, `infoflot`, `volga`

**Возвращает:**
- `string` - Абсолютный путь к файлу базы данных

**Исключения:**
- `Exception` - Если источник не найден в конфигурации

### `TransferConfig::getAllPaths()`

Получить все пути к базам данных.

**Возвращает:**
- `array` - Массив `[source => absolute_path]`

## Интеграция

Все классы Database (`GamaDatabase`, `GermesDatabase`, `InfoflotDatabase`, `VolgaDatabase`, `WaterwayDatabase`) автоматически используют `TransferConfig` для получения путей к базам данных.

## Примечания

- Переменные окружения читаются через стандартную функцию Laravel `env()`
- Если переменная окружения не задана, используется путь по умолчанию
- Все пути разрешаются через `base_path()`, что обеспечивает корректную работу в различных окружениях

