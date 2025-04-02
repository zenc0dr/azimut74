<?php

namespace Zen\Fetcher\Classes\Services\Database;

use Zen\Fetcher\Traits\SingletonTrait;
use Zen\Fetcher\Classes\Services\PoolManager;
use Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use DB;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\Connection;

class MySqlManager
{
    use SingletonTrait;


    private function getConnection($connection): array
    {
        if (is_string($connection)) {
            $connection = explode('@', $connection);
            $connection = fetcher()->pool($connection[0])->settings($connection[1]);
        }
        return $connection;
    }

    /**
     * @param array|string $connection - ex: pool_name@settings_key или массив c настройками
     */
    public function createDatabase($connection): bool
    {
        $cn = (object)$this->getConnection($connection);
        $mysql = $this->rootConnection();
        $database_is_exists = boolval($mysql->query("SHOW DATABASES LIKE '$cn->database'")->rowCount());
        if (!$database_is_exists) {
            $mysql->query("CREATE DATABASE `$cn->database` COLLATE 'utf8mb4_general_ci'");
            $mysql->query("CREATE USER '$cn->username'@'$cn->host' IDENTIFIED BY '$cn->password'");
            $mysql->query("GRANT ALL PRIVILEGES ON `$cn->database`.* TO '$cn->username'@'$cn->host' WITH GRANT OPTION");
            return true;
        }
        return false;
    }

    private function rootConnection()
    {
        $db_host = fetcher()->settings('db_host');
        $db_port = fetcher()->settings('db_port');
        $db_user = fetcher()->settings('db_user');
        $db_pass = fetcher()->settings('db_pass');

        if ($db_host === 'ENV') {
            $db_host = env('MYSQL_ROOT_DB_HOST');
        }
        if (!$db_host) {
            $db_host = 'localhost';
        }
        if ($db_port === 'ENV') {
            $db_port = env('MYSQL_ROOT_DB_PORT');
        }
        if (!$db_port) {
            $db_port = 3306;
        }

        if ($db_user === 'ENV') {
            $db_user = env('MYSQL_ROOT_DB_USER');
        }
        if ($db_pass === 'ENV') {
            $db_pass = env('MYSQL_ROOT_DB_PASS');
        }

        $connector = new MySqlConnector();
        $config = [
            'username' => $db_user,
            'password' => $db_pass,
            'host' => $db_host,
            'port' => $db_port,
            'database' => null
        ];
        return $connector->connect($config);
    }

    public function makeConnection(array $connection, string $connection_name): void
    {
        $cn = (object)$connection;
        Config::set("database.connections.$connection_name", [
            'driver' => 'mysql',
            'host' => $cn->host ?? 'localhost',
            'port' => $cn->port ?? 3306,
            'database' => $cn->database ?? null,
            'username' => $cn->username,
            'password' => $cn->password,
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);
    }

    public function dbConnection($connection)
    {
        $cn = $this->getConnection($connection);
        $this->makeConnection($cn, $cn['database']);
        return DB::connection($cn['database']);
    }

    public function schemaCreate(
        $connection,
        string $table_name,
        \Closure $schema,
        bool $only_create = false
    ): bool {
        $cn = $this->getConnection($connection);
        $this->makeConnection($cn, $cn['database']);

        if (Schema::connection($cn['database'])->hasTable($table_name)) {
            if ($only_create) {
                return false;
            }
            Schema::connection($cn['database'])
                ->table($table_name, function (Blueprint $table) use ($schema) {
                    $schema($table);
                });
            return false;
        } else {
            Schema::connection($cn['database'])
                ->create($table_name, function (Blueprint $table) use ($schema) {
                    $schema($table);
                });
            return true;
        }
    }
}
