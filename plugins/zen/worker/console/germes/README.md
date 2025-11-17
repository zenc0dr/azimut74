# Парсер Germes - Фаза 1

Парсер для получения данных о круизах из XML API Germes и сохранения в SQLite базу данных.

## Быстрый старт

```bash
# Полный парсинг с очисткой
php artisan worker:germes-parse --clear

# Тестовый парсинг (10 круизов)
php artisan worker:germes-parse --clear --limit=10
```

## Структура

- `GermesParse.php` - Консольная команда
- `GermesDatabase.php` - Работа с SQLite
- `GermesApiClient.php` - Клиент для XML API
- `GermesDataProcessor.php` - Обработка данных
- `GermesCache.php` - Файловый кеш
- `germes_data.sqlite` - SQLite база данных

## Документация

Полная документация: `docs/sources/germes.md`

