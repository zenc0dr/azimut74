<?php namespace Zen\Worker\Api;

use Input;
use PDO;
use Exception;

class Sqlite
{
    /**
     * Выполнение SQL запроса к SQLite базе
     *
     * Параметры:
     * - db: путь к SQLite файлу относительно ocms/ (например: "plugins/zen/worker/console/gama/gama_data.sqlite")
     * - query: SQL запрос (только SELECT для безопасности)
     *
     * Пример:
     * http://azimut74/zen/worker/api/sqlite:query?db=plugins/zen/worker/console/gama/gama_data.sqlite&query=SELECT * FROM cruises LIMIT 10
     */
    public function query()
    {
        $dbPath = Input::get('db');
        $sqlQuery = Input::get('query');

        // Валидация параметров
        if (!$dbPath) {
            return response()->json(['error' => 'Parameter "db" is required'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (!$sqlQuery) {
            return response()->json(['error' => 'Parameter "query" is required'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        // Нормализуем путь к базе данных
        $dbPath = $this->normalizeDbPath($dbPath);
        
        if (!$dbPath) {
            return response()->json(['error' => 'Invalid database path'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        // Проверяем существование файла
        if (!file_exists($dbPath)) {
            return response()->json(['error' => 'Database file not found', 'path' => $dbPath], 404, [], JSON_UNESCAPED_UNICODE);
        }

        // Валидация SQL запроса (только SELECT для безопасности)
        if (!$this->isSelectQuery($sqlQuery)) {
            return response()->json(['error' => 'Only SELECT queries are allowed'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            // Подключаемся к базе
            $pdo = new PDO("sqlite:" . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Выполняем запрос
            $stmt = $pdo->prepare($sqlQuery);
            $stmt->execute();

            // Получаем результаты
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $rowCount = count($results);

            // Получаем информацию о колонках
            $columns = [];
            if ($rowCount > 0) {
                $columns = array_keys($results[0]);
            } else {
                // Если нет результатов, получаем колонки из метаданных
                $columnCount = $stmt->columnCount();
                for ($i = 0; $i < $columnCount; $i++) {
                    $columns[] = $stmt->getColumnMeta($i)['name'];
                }
            }

            return response()->json([
                'success' => true,
                'database' => basename($dbPath),
                'query' => $sqlQuery,
                'columns' => $columns,
                'row_count' => $rowCount,
                'data' => $results
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'database' => basename($dbPath),
                'query' => $sqlQuery
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Получение списка таблиц в базе данных
     *
     * Параметры:
     * - db: путь к SQLite файлу
     *
     * Пример:
     * http://azimut74/zen/worker/api/sqlite:tables?db=plugins/zen/worker/console/gama/gama_data.sqlite
     */
    public function tables()
    {
        $dbPath = Input::get('db');

        if (!$dbPath) {
            return response()->json(['error' => 'Parameter "db" is required'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $dbPath = $this->normalizeDbPath($dbPath);
        
        if (!$dbPath) {
            return response()->json(['error' => 'Invalid database path'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (!file_exists($dbPath)) {
            return response()->json(['error' => 'Database file not found', 'path' => $dbPath], 404, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $pdo = new PDO("sqlite:" . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Получаем список таблиц
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return response()->json([
                'success' => true,
                'database' => basename($dbPath),
                'tables' => $tables,
                'count' => count($tables)
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'database' => basename($dbPath)
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Получение структуры таблицы
     *
     * Параметры:
     * - db: путь к SQLite файлу
     * - table: имя таблицы
     *
     * Пример:
     * http://azimut74/zen/worker/api/sqlite:structure?db=plugins/zen/worker/console/gama/gama_data.sqlite&table=cruises
     */
    public function structure()
    {
        $dbPath = Input::get('db');
        $tableName = Input::get('table');

        if (!$dbPath) {
            return response()->json(['error' => 'Parameter "db" is required'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (!$tableName) {
            return response()->json(['error' => 'Parameter "table" is required'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $dbPath = $this->normalizeDbPath($dbPath);
        
        if (!$dbPath) {
            return response()->json(['error' => 'Invalid database path'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        if (!file_exists($dbPath)) {
            return response()->json(['error' => 'Database file not found', 'path' => $dbPath], 404, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $pdo = new PDO("sqlite:" . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Получаем структуру таблицы
            $stmt = $pdo->query("PRAGMA table_info({$tableName})");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($columns)) {
                return response()->json(['error' => 'Table not found', 'table' => $tableName], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // Получаем количество записей
            $countStmt = $pdo->query("SELECT COUNT(*) FROM {$tableName}");
            $rowCount = $countStmt->fetchColumn();

            return response()->json([
                'success' => true,
                'database' => basename($dbPath),
                'table' => $tableName,
                'row_count' => (int)$rowCount,
                'columns' => $columns
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'database' => basename($dbPath),
                'table' => $tableName
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Нормализация пути к базе данных
     * Безопасно обрабатывает путь, разрешая только файлы внутри ocms/
     *
     * @param string $path Путь к базе данных
     * @return string|null Абсолютный путь или null если невалидный
     */
    private function normalizeDbPath($path)
    {
        // Убираем начальные слэши и точки
        $path = ltrim($path, '/.');

        // Убираем попытки выйти за пределы директории
        $path = str_replace('..', '', $path);

        // Убираем двойные слэши
        $path = preg_replace('#/+#', '/', $path);

        // Строим абсолютный путь относительно ocms/
        $basePath = base_path();
        $fullPath = $basePath . '/' . $path;

        // Проверяем, что путь находится внутри ocms/
        $realBasePath = realpath($basePath);
        $realFullPath = realpath(dirname($fullPath));

        if (!$realBasePath || !$realFullPath) {
            return null;
        }

        // Проверяем, что путь не выходит за пределы базовой директории
        if (strpos($realFullPath, $realBasePath) !== 0) {
            return null;
        }

        return $fullPath;
    }

    /**
     * Проверка, что запрос является SELECT запросом
     *
     * @param string $query SQL запрос
     * @return bool
     */
    private function isSelectQuery($query)
    {
        // Убираем пробелы и переводы строк в начале
        $query = trim($query);

        // Проверяем, что запрос начинается с SELECT (без учета регистра)
        return stripos($query, 'SELECT') === 0;
    }
}

