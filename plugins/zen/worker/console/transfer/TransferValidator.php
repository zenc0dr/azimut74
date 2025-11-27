<?php namespace Zen\Worker\Console\transfer;

use Exception;

/**
 * Базовый класс для валидации SQLite баз перед импортом в MySQL
 */
abstract class TransferValidator
{
    /**
     * @var object Экземпляр SQLite Database
     */
    protected $db;

    /**
     * @var string Название источника (gama, germes, infoflot, volga, waterway)
     */
    protected $sourceName;

    /**
     * @var array Список ошибок валидации
     */
    protected $errors = [];

    /**
     * @var array Список предупреждений валидации
     */
    protected $warnings = [];

    /**
     * Конструктор
     * @param object $db Экземпляр SQLite Database
     * @param string $sourceName Название источника
     */
    public function __construct($db, $sourceName)
    {
        $this->db = $db;
        $this->sourceName = $sourceName;
    }

    /**
     * Основной метод валидации
     * @return bool true если валидация прошла успешно, false если есть ошибки
     */
    public function validate(): bool
    {
        $this->errors = [];
        $this->warnings = [];

        $this->validateStructure();
        $this->validateIntegrity();
        $this->validateData();

        return empty($this->errors);
    }

    /**
     * Проверка структуры базы данных
     * Должен быть реализован в дочерних классах
     */
    abstract protected function validateStructure();

    /**
     * Проверка целостности данных
     * Должен быть реализован в дочерних классах
     */
    abstract protected function validateIntegrity();

    /**
     * Проверка валидности данных
     * Должен быть реализован в дочерних классах
     */
    abstract protected function validateData();

    /**
     * Добавление ошибки валидации
     * @param string $message Сообщение об ошибке
     * @param array $context Дополнительный контекст
     */
    protected function addError($message, $context = [])
    {
        $this->errors[] = [
            'message' => $message,
            'context' => $context,
            'source' => $this->sourceName
        ];
    }

    /**
     * Добавление предупреждения валидации
     * @param string $message Сообщение предупреждения
     * @param array $context Дополнительный контекст
     */
    protected function addWarning($message, $context = [])
    {
        $this->warnings[] = [
            'message' => $message,
            'context' => $context,
            'source' => $this->sourceName
        ];
    }

    /**
     * Получение списка ошибок
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получение списка предупреждений
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Проверка наличия ошибок
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Проверка наличия предупреждений
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Проверка существования таблицы в базе данных
     * @param string $tableName Название таблицы
     * @return bool
     */
    protected function tableExists($tableName): bool
    {
        try {
            $stmt = $this->db->getPdo()->prepare(
                "SELECT name FROM sqlite_master WHERE type='table' AND name=?"
            );
            $stmt->execute([$tableName]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Выполнение SQL запроса и получение результата
     * @param string $sql SQL запрос
     * @param array $params Параметры запроса
     * @return array
     */
    protected function query($sql, $params = []): array
    {
        try {
            $stmt = $this->db->getPdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->addError("Ошибка выполнения запроса: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            return [];
        }
    }

    /**
     * Получение PDO объекта из Database класса
     * @return \PDO
     */
    protected function getPdo(): \PDO
    {
        // Все Database классы имеют метод getPdo() или свойство pdo
        if (method_exists($this->db, 'getPdo')) {
            return $this->db->getPdo();
        }
        
        // Если метода нет, используем рефлексию для доступа к приватному свойству
        $reflection = new \ReflectionClass($this->db);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        return $property->getValue($this->db);
    }
}

