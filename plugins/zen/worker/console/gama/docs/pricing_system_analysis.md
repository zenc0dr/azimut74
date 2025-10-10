# Анализ системы цен в Mcmraak\Rivercrs

## Обзор системы

Система цен в Mcmraak\Rivercrs состоит из двух основных таблиц:
1. **mcmraak_rivercrs_pricing** - основная таблица цен
2. **mcmraak_rivercrs_nprices** - дублирующая таблица с дополнительными полями

## Ключевые классы и методы

### 1. RiverCrs (Zen\Worker\Pools\RiverCrs)
Базовый класс для всех парсеров, содержит методы-обертки:

- `updateCabinPrice($checkin_id, $cabin_id, $price_value, $price2_value = null)` - добавление цены
- `getCabinCategoryId($category_name, $motorship_id, $eds_code, $places)` - получение/создание категории кают
- `isCabinNotLet($cabin_name, $motorship_id)` - проверка исключений кают
- `fixCheckin($checkin_id)` - синхронизация заезда

### 2. Getter (Mcmraak\Rivercrs\Classes\Getter)
Основной класс для работы с данными:

- `updateCabinPrice()` - реализация добавления цен
- `deckPivotCheck()` - проверка связи палуба-каюта

### 3. Pricing (Mcmraak\Rivercrs\Models\Pricing)
Модель для работы с таблицей цен:

- `Pivot($checkin_id, $motorship_id)` - получение цен для заезда
- `CheckinPrice($checkin_id, $motorship_id)` - получение цен заезда

## Анализ GamaV2.php

### Механизм добавления цен в GamaV2:

```php
// 1. Получение цен через getCruisePrices()
$prices = $this->getCruisePrices($navigation_id, $cruise_id, $ship, $gama_ship_id);

// 2. Проверка наличия цен
if (!$prices) {
    ProcessLog::add("Для круиза gama:$cruise_id отсутствуют цены, круиз игнорирован.");
    continue;
}

// 3. Сохранение заезда
$checkin->save();
$this->fixCheckin($checkin->id);

// 4. Подготовка данных для вставки
$insert_prices = [];
foreach ($prices as $price) {
    $insert_prices[] = [
        'checkin_id' => $checkin->id,
        'cabin_id' => $price['cabin_id'],
        'price_a' => $price['price_1']
    ];
}

// 5. Удаление старых цен и вставка новых
DB::table('mcmraak_rivercrs_pricing')
    ->where('checkin_id', $checkin->id)
    ->delete();

DB::table('mcmraak_rivercrs_pricing')
    ->insert($insert_prices);
```

### Ключевые особенности:

1. **Проверка наличия цен**: Круиз игнорируется, если нет цен
2. **Прямая работа с БД**: Используется `DB::table()->insert()` вместо `updateCabinPrice()`
3. **Очистка старых цен**: Удаляются все старые цены перед вставкой новых
4. **Структура данных**: `price_a` для основной цены, `price_b` для дополнительной

## Проблемы в текущей реализации GamaMainDbImporter

### 1. Неправильное использование updateCabinPrice()
Текущий код использует `$this->updateCabinPrice()`, но это метод-обертка, который:
- Удаляет старые цены для конкретной каюты
- Создает новую запись с одной ценой
- Не подходит для массовой вставки

### 2. Отсутствие проверки наличия цен
Нет проверки, есть ли цены для круиза перед его импортом.

### 3. Неправильная структура данных
Используются поля `price_1`/`price_2` вместо `price_a`/`price_b`.

## Рекомендации по исправлению

### 1. Использовать прямой подход как в GamaV2
```php
// Подготовка данных
$insert_prices = [];
foreach ($prices as $price) {
    $insert_prices[] = [
        'checkin_id' => $checkin->id,
        'cabin_id' => $price['cabin_id'],
        'price_a' => $price['price_1'],
        'price_b' => $price['price_2'] ?? null
    ];
}

// Массовая вставка
DB::table('mcmraak_rivercrs_pricing')
    ->where('checkin_id', $checkin->id)
    ->delete();

DB::table('mcmraak_rivercrs_pricing')
    ->insert($insert_prices);
```

### 2. Добавить проверку наличия цен
```php
if (empty($prices)) {
    ProcessLog::add("Для круиза gama:$cruise_id отсутствуют цены, круиз игнорирован.");
    continue;
}
```

### 3. Добавить валидацию после вставки
```php
// Проверка, что цены действительно добавились
$inserted_count = DB::table('mcmraak_rivercrs_pricing')
    ->where('checkin_id', $checkin->id)
    ->count();

ProcessLog::add("Добавлено цен для круиза {$checkin->id}: {$inserted_count}");
```

## Структура таблиц

### mcmraak_rivercrs_pricing
- `checkin_id` - ID заезда
- `cabin_id` - ID каюты
- `price_a` - основная цена
- `price_b` - дополнительная цена (может быть NULL)
- `desc` - описание (обычно NULL)

### mcmraak_rivercrs_nprices
- `checkin_id` - ID заезда
- `deck_id` - ID палубы
- `cabin_id` - ID каюты
- `places_qnt` - количество мест
- `price` - цена

## Следующие шаги

1. Исправить метод `importPrices()` в `GamaMainDbImporter`
2. Добавить проверку наличия цен перед импортом круиза
3. Добавить валидацию после вставки цен
4. Протестировать на ограниченном наборе данных
5. Запустить полный импорт
