# Консольные команды Zen.Worker

Все команды запускаются **внутри контейнера** (PHP 7.4):

```bash
docker exec azimut74-web php artisan <команда> [опции]
```

---

## 📋 Содержание

- [Архитектура парсеров](#архитектура-парсеров)
- [Sync-команды (рекомендуемые)](#sync-команды-рекомендуемые)
- [Parse-команды (Фаза 1)](#parse-команды-фаза-1)
- [Transfer-команда (Фаза 2)](#transfer-команда-фаза-2)
- [Служебные команды](#служебные-команды)
- [Примеры использования](#примеры-использования)

---

## Архитектура парсеров

Парсеры работают в **2 фазы**:

```
┌─────────────────────────────────────────────────────────────────┐
│  Фаза 1 (parse)                                                 │
│  Внешний API/XML → SQLite + Файловый кеш                        │
│                                                                 │
│  worker:{source}-parse                                          │
│  storage/parsers_db/{source}_data.sqlite                        │
│  storage/parsers_cache/{source}/                                │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  Фаза 2 (transfer)                                              │
│  SQLite → MySQL (основная БД azimut74)                          │
│                                                                 │
│  worker:transfer --source={source}                              │
└─────────────────────────────────────────────────────────────────┘
```

**Источники данных:**
- `gama` — Gama (XML архив + API)
- `germes` — Germes (API)
- `infoflot` — Infoflot (API)
- `volga` — Volga (XML)
- `waterway` — Waterway (API)

---

## Sync-команды (рекомендуемые)

Sync-команды объединяют обе фазы в одну команду. **Рекомендуется использовать их** для стандартных операций.

### worker:gama-sync

```bash
php artisan worker:gama-sync [опции]
```

**Описание:** Gama: parse (SQLite) + transfer (MySQL)

**Опции:**

| Опция | Короткая | Описание | По умолчанию |
|-------|----------|----------|--------------|
| `--parse-only` | | Только фаза 1 (API/архив → SQLite) | - |
| `--transfer-only` | | Только фаза 2 (SQLite → MySQL) | - |
| `--import` | | Включить импорт на фазе 2 (иначе только валидация) | - |
| `--timeout` | `-t` | Таймаут HTTP запросов (сек) | 30 |
| `--clear` | `-c` | Очистить SQLite перед парсингом | - |
| `--clear_cache` | | Очистить файловый кеш API | - |
| `--limit` | `-l` | Лимит записей для отладки | - |
| `--validate-only` | | Только валидация SQLite | - |
| `--skip-validation` | | Пропустить валидацию | - |

---

### worker:germes-sync

```bash
php artisan worker:germes-sync [опции]
```

**Описание:** Germes: parse (SQLite) + transfer (MySQL)

**Опции:** аналогичны `worker:gama-sync`

---

### worker:infoflot-sync

```bash
php artisan worker:infoflot-sync [опции]
```

**Описание:** Infoflot: parse (SQLite) + transfer (MySQL)

**Опции:** аналогичны `worker:gama-sync`

---

### worker:volga-sync

```bash
php artisan worker:volga-sync [опции]
```

**Описание:** Volga: parse (SQLite) + transfer (MySQL)

**Опции:** аналогичны `worker:gama-sync`

---

### worker:waterway-sync

```bash
php artisan worker:waterway-sync [опции]
```

**Описание:** Waterway: parse (SQLite) + transfer (MySQL)

**Дополнительные опции:**

| Опция | Описание | По умолчанию |
|-------|----------|--------------|
| `--limit_ships` | Лимит теплоходов | - |
| `--limit_cruises` | Лимит круизов | - |
| `--limit_cruises_per_ship` | Лимит круизов на теплоход | - |
| `--progress_every` | Прогресс каждые N круизов | 1 |

---

## Parse-команды (Фаза 1)

Команды парсинга загружают данные из внешних источников в SQLite.

### worker:gama-parse

```bash
php artisan worker:gama-parse [опции]
```

**Описание:** Парсинг круизов Gama с сохранением в SQLite

**Источник данных:**
- Архив: `https://gama-nn.ru/satellite/xml/zip/`
- API маршрутов: `https://gama-nn.ru/satellite/route/{id}/`

**Результат:**
- SQLite: `storage/parsers_db/gama_data.sqlite`
- Кеш: `storage/parsers_cache/gama/`

**Опции:**

| Опция | Короткая | Описание | По умолчанию |
|-------|----------|----------|--------------|
| `--timeout` | `-t` | Таймаут HTTP запросов (сек) | 30 |
| `--clear` | `-c` | Очистить SQLite перед парсингом | - |
| `--clear_cache` | | Очистить файловый кеш | - |
| `--limit` | `-l` | Лимит записей для тестирования | - |

---

### worker:germes-parse

```bash
php artisan worker:germes-parse [опции]
```

**Описание:** Парсинг круизов Germes с сохранением в SQLite

**Результат:**
- SQLite: `storage/parsers_db/germes_data.sqlite`
- Кеш: `storage/parsers_cache/germes/`

**Опции:** аналогичны `worker:gama-parse`

---

### worker:infoflot-parse

```bash
php artisan worker:infoflot-parse [опции]
```

**Описание:** Парсинг круизов Infoflot с сохранением в SQLite

**Результат:**
- SQLite: `storage/parsers_db/infoflot_data.sqlite`
- Кеш: `storage/parsers_cache/infoflot/`

**Опции:**

| Опция | Короткая | Описание | По умолчанию |
|-------|----------|----------|--------------|
| `--timeout` | `-t` | Таймаут HTTP запросов (сек) | 30 |
| `--clear` | `-c` | Очистить SQLite перед парсингом | - |
| `--clear_cache` | | Очистить файловый кеш | - |
| `--limit` | `-l` | Лимит записей для тестирования | - |
| `--api-key` | `-k` | API ключ Infoflot | (из конфига) |

---

### worker:volga-parse

```bash
php artisan worker:volga-parse [опции]
```

**Описание:** Парсинг круизов Volga с сохранением в SQLite

**Источник данных:** XML файл

**Результат:**
- SQLite: `storage/parsers_db/volga_data.sqlite`
- Кеш: `storage/parsers_cache/volga/`

**Опции:**

| Опция | Короткая | Описание | По умолчанию |
|-------|----------|----------|--------------|
| `--timeout` | `-t` | Таймаут HTTP запросов (сек) | 30 |
| `--clear` | `-c` | Очистить SQLite перед парсингом | - |
| `--clear_cache` | | Очистить XML кеш | - |
| `--limit` | `-l` | Лимит записей для тестирования | - |
| `--next-url` | `-u` | URL источника XML | `http://test.volgawolga.ru/xml/daily2024.xml` |

---

### worker:waterway-parse

```bash
php artisan worker:waterway-parse [опции]
```

**Описание:** Парсинг круизов Waterway с сохранением в SQLite

**Результат:**
- SQLite: `storage/parsers_db/waterway_data.sqlite`
- Кеш: `storage/parsers_cache/waterway/`

**Опции:**

| Опция | Короткая | Описание | По умолчанию |
|-------|----------|----------|--------------|
| `--timeout` | `-t` | Таймаут HTTP запросов (сек) | 30 |
| `--clear` | `-c` | Очистить SQLite перед парсингом | - |
| `--clear_cache` | | Очистить файловый кеш | - |
| `--limit` | `-l` | Legacy лимит круизов | - |
| `--limit_ships` | | Лимит теплоходов | - |
| `--limit_cruises` | | Лимит круизов | - |
| `--limit_cruises_per_ship` | | Лимит круизов на теплоход | - |
| `--progress_every` | | Прогресс каждые N круизов | 1 |

---

## Transfer-команда (Фаза 2)

### worker:transfer

```bash
php artisan worker:transfer [опции]
```

**Описание:** Импорт данных из SQLite баз в MySQL (основная БД)

**Опции:**

| Опция | Короткая | Описание | По умолчанию |
|-------|----------|----------|--------------|
| `--source` | `-s` | Источник (gama, germes, infoflot, volga, waterway, all) | all |
| `--validate-only` | | Только валидация без импорта | - |
| `--skip-validation` | | Пропустить валидацию | - |
| `--no-telegram` | | Отключить Telegram-уведомления | - |

**Примеры:**

```bash
# Валидация всех источников
php artisan worker:transfer

# Валидация только Gama
php artisan worker:transfer --source=gama

# Импорт Gama с валидацией
php artisan worker:transfer --source=gama

# Импорт Gama без валидации (быстрее, опасно)
php artisan worker:transfer --source=gama --skip-validation
```

---

## Служебные команды

### worker:clear-cache

```bash
php artisan worker:clear-cache
```

**Описание:** Очистка файлового кеша **всех** парсеров

**Очищает директории:**
- `storage/parsers_cache/gama/`
- `storage/parsers_cache/germes/`
- `storage/parsers_cache/infoflot/`
- `storage/parsers_cache/volga/`
- `storage/parsers_cache/waterway/`

⚠️ **Внимание:** После очистки кеша следующий парсинг будет значительно дольше!

---

### worker:clear-cruises

```bash
php artisan worker:clear-cruises
```

**Описание:** Очистка базы данных круизов (MySQL)

⚠️ **Опасно:** Удаляет все круизы из основной базы данных!

---

### worker:go

```bash
php artisan worker:go
```

**Описание:** Запуск очереди задач (legacy)

---

### worker:test-unified-databases

```bash
php artisan worker:test-unified-databases
```

**Описание:** Тестирование единой структуры SQLite баз данных

---

### worker:waterway-check-roomclass

```bash
php artisan worker:waterway-check-roomclass
```

**Описание:** Проверка структуры roomClass в ответе API Waterway

---

## Примеры использования

### Полный цикл обновления одного источника

```bash
# Вариант 1: Sync-команда (рекомендуется)
docker exec azimut74-web php artisan worker:gama-sync --clear_cache --clear --import

# Вариант 2: Раздельные фазы
docker exec azimut74-web php artisan worker:gama-parse --clear_cache --clear
docker exec azimut74-web php artisan worker:transfer --source=gama
```

### Тестовый прогон с лимитом

```bash
# Парсинг только 10 круизов для проверки
docker exec azimut74-web php artisan worker:waterway-sync --limit=10 --parse-only

# Waterway с детальными лимитами
docker exec azimut74-web php artisan worker:waterway-sync \
  --limit_ships=2 \
  --limit_cruises_per_ship=5 \
  --parse-only
```

### Только валидация (без записи в MySQL)

```bash
# Валидация всех источников
docker exec azimut74-web php artisan worker:transfer

# Валидация конкретного источника
docker exec azimut74-web php artisan worker:gama-sync --validate-only
```

### Прогрев кеша

```bash
# Прогреваем кеш Waterway (без очистки, только скачиваем новое)
docker exec azimut74-web php artisan worker:waterway-parse

# Прогреваем с нуля (очистка + скачивание)
docker exec azimut74-web php artisan worker:waterway-parse --clear_cache --clear
```

### Обновление всех источников

```bash
# Последовательный запуск всех sync-команд с импортом
for source in gama germes infoflot volga waterway; do
  docker exec azimut74-web php artisan worker:${source}-sync --import
done
```

---

## Пути файлов

| Тип | Путь |
|-----|------|
| SQLite базы | `storage/parsers_db/{source}_data.sqlite` |
| Файловый кеш | `storage/parsers_cache/{source}/` |
| Логи | `storage/logs/worker.log` |
| Логи ошибок трансфера | `storage/logs/transfer_errors.log` |

---

## Telegram уведомления

Sync и Transfer команды отправляют уведомления в Telegram о прогрессе выполнения. Для отключения используйте `--no-telegram`.

Настройки Telegram находятся в конфигурации плагина Zen.Worker.
