<?php namespace Zen\Worker\Api;

use Input;
use DB;
use Exception;
use PDO;

class Mysql
{
    private $token = 'xDenBhdu6fTgd3nbbBd45oOpd6gGssX';

    /**
     * Выполнение SQL запроса к MySQL базе данных
     *
     * Параметры:
     * - token: токен доступа
     * - query: SQL запрос (любой: SELECT, INSERT, UPDATE, DELETE)
     *
     * Пример:
     * http://azimut74/zen/worker/api/mysql:query?token=xxx&query=SELECT * FROM users LIMIT 10
     * http://azimut74/zen/worker/api/mysql:query?token=xxx&query=UPDATE users SET name='test' WHERE id=1
     */
    public function query()
    {
        // Проверка токена
        if (!$this->checkToken()) {
            return response()->json(['error' => 'Access denied. Invalid token.'], 403, [], JSON_UNESCAPED_UNICODE);
        }

        $sqlQuery = Input::get('query');

        if (!$sqlQuery) {
            return response()->json(['error' => 'Parameter "query" is required'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $connection = $this->getConnection();
            $pdo = $connection->getPdo();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Определяем тип запроса
            $queryType = $this->getQueryType($sqlQuery);

            // Выполняем запрос
            $stmt = $pdo->prepare($sqlQuery);
            $stmt->execute();

            // Обрабатываем результат в зависимости от типа запроса
            if ($queryType === 'SELECT') {
                // Для SELECT запросов возвращаем данные
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
                        $meta = $stmt->getColumnMeta($i);
                        $columns[] = $meta['name'];
                    }
                }

                return response()->json([
                    'success' => true,
                    'database' => $this->getDatabaseName(),
                    'query' => $sqlQuery,
                    'query_type' => 'SELECT',
                    'columns' => $columns,
                    'row_count' => $rowCount,
                    'data' => $results
                ], 200, [], JSON_UNESCAPED_UNICODE);
            } else {
                // Для INSERT, UPDATE, DELETE возвращаем количество затронутых строк
                $affectedRows = $stmt->rowCount();
                $lastInsertId = null;

                if ($queryType === 'INSERT') {
                    $lastInsertId = $pdo->lastInsertId();
                }

                return response()->json([
                    'success' => true,
                    'database' => $this->getDatabaseName(),
                    'query' => $sqlQuery,
                    'query_type' => $queryType,
                    'affected_rows' => $affectedRows,
                    'last_insert_id' => $lastInsertId
                ], 200, [], JSON_UNESCAPED_UNICODE);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'database' => $this->getDatabaseName(),
                'query' => $sqlQuery
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Получение списка таблиц в базе данных
     *
     * Параметры:
     * - token: токен доступа
     *
     * Пример:
     * http://azimut74/zen/worker/api/mysql:tables?token=xxx
     */
    public function tables()
    {
        // Проверка токена
        if (!$this->checkToken()) {
            return response()->json(['error' => 'Access denied. Invalid token.'], 403, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $connection = $this->getConnection();
            $pdo = $connection->getPdo();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Получаем список таблиц
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return response()->json([
                'success' => true,
                'database' => $this->getDatabaseName(),
                'tables' => $tables,
                'count' => count($tables)
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'database' => $this->getDatabaseName()
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Получение структуры таблицы
     *
     * Параметры:
     * - token: токен доступа
     * - table: имя таблицы
     *
     * Пример:
     * http://azimut74/zen/worker/api/mysql:structure?token=xxx&table=users
     */
    public function structure()
    {
        // Проверка токена
        if (!$this->checkToken()) {
            return response()->json(['error' => 'Access denied. Invalid token.'], 403, [], JSON_UNESCAPED_UNICODE);
        }

        $tableName = Input::get('table');

        if (!$tableName) {
            return response()->json(['error' => 'Parameter "table" is required'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        try {
            $connection = $this->getConnection();
            $pdo = $connection->getPdo();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Получаем структуру таблицы
            $stmt = $pdo->query("DESCRIBE `{$tableName}`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($columns)) {
                return response()->json(['error' => 'Table not found', 'table' => $tableName], 404, [], JSON_UNESCAPED_UNICODE);
            }

            // Получаем количество записей
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$tableName}`");
            $rowCount = $countStmt->fetchColumn();

            return response()->json([
                'success' => true,
                'database' => $this->getDatabaseName(),
                'table' => $tableName,
                'row_count' => (int)$rowCount,
                'columns' => $columns
            ], 200, [], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'database' => $this->getDatabaseName(),
                'table' => $tableName
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Проверка токена доступа
     *
     * @return bool
     */
    private function checkToken()
    {
        $token = Input::get('token');
        return $token === $this->token;
    }

    /**
     * Получение подключения к базе данных
     *
     * @return \Illuminate\Database\Connection
     */
    private function getConnection()
    {
        return DB::connection('mysql');
    }

    /**
     * Получение имени базы данных
     *
     * @return string
     */
    private function getDatabaseName()
    {
        $config = config('database.connections.mysql');
        return $config['database'] ?? 'unknown';
    }

    /**
     * Определение типа SQL запроса
     *
     * @param string $query SQL запрос
     * @return string Тип запроса: SELECT, INSERT, UPDATE, DELETE, или UNKNOWN
     */
    private function getQueryType($query)
    {
        $query = trim($query);
        $query = preg_replace('/\s+/', ' ', $query);
        $firstWord = strtoupper(explode(' ', $query)[0]);

        $types = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'REPLACE', 'TRUNCATE', 'DROP', 'CREATE', 'ALTER'];
        
        foreach ($types as $type) {
            if (strpos($firstWord, $type) === 0) {
                return $type;
            }
        }

        return 'UNKNOWN';
    }
}

