-- Миграция базы данных Infoflot парсера
-- Исправление проблем:
-- 1. Удаление избыточного поля type_id из таблицы prices
-- 2. Удаление неиспользуемой таблицы cabins

-- Шаг 1: Создание новой таблицы prices без поля type_id
CREATE TABLE IF NOT EXISTS prices_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cruise_id INTEGER,
    cabin_category_id INTEGER,
    type_name TEXT,
    price_adult INTEGER,
    price_default INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cruise_id) REFERENCES cruises(id),
    FOREIGN KEY (cabin_category_id) REFERENCES cabin_categories(id)
);

-- Шаг 2: Копирование данных из старой таблицы prices (без type_id)
INSERT INTO prices_new (id, cruise_id, cabin_category_id, type_name, price_adult, price_default, created_at)
SELECT id, cruise_id, cabin_category_id, type_name, price_adult, price_default, created_at
FROM prices;

-- Шаг 3: Удаление старой таблицы prices
DROP TABLE IF EXISTS prices;

-- Шаг 4: Переименование новой таблицы в prices
ALTER TABLE prices_new RENAME TO prices;

-- Шаг 5: Восстановление индекса
CREATE INDEX IF NOT EXISTS idx_prices_cruise_id ON prices(cruise_id);

-- Шаг 6: Удаление неиспользуемой таблицы cabins
DROP TABLE IF EXISTS cabins;

