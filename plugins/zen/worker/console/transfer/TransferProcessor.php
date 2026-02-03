<?php namespace Zen\Worker\Console\transfer;

use Zen\Worker\Pools\RiverCrs;
use Zen\Worker\Classes\ProcessLog;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use DB;

/**
 * Базовый класс для обработки данных из SQLite и импорта в MySQL
 */
abstract class TransferProcessor
{
    /**
     * @var object Экземпляр SQLite Database
     */
    protected $db;

    /**
     * @var RiverCrs Экземпляр RiverCrs для работы с MySQL
     */
    protected $riverCrs;

    /**
     * @var string Название источника (gama, germes, infoflot, volga, waterway)
     */
    protected $sourceName;

    /**
     * @var string EDS код (gama, germes, infoflot, volga, waterway)
     */
    protected $edsCode;

    /**
     * @var string Поле ID источника (gama_id, germes_id, infoflot_id, volga_id, waterway_id)
     */
    protected $edsIdField;

    /**
     * Конструктор
     * @param object $db Экземпляр SQLite Database
     * @param string $sourceName Название источника
     * @param string $edsCode EDS код
     * @param string $edsIdField Поле ID источника
     */
    public function __construct($db, $sourceName, $edsCode, $edsIdField)
    {
        $this->db = $db;
        $this->sourceName = $sourceName;
        $this->edsCode = $edsCode;
        $this->edsIdField = $edsIdField;
        $this->riverCrs = new RiverCrs();
    }

    /**
     * Основной метод обработки всех круизов
     * Должен быть реализован в дочерних классах
     */
    abstract public function process();

    /**
     * Импорт одного круиза
     * Должен быть реализован в дочерних классах
     * @param array $cruise Данные круиза из SQLite
     * @return int|null ID созданного/обновленного заезда или null при ошибке
     */
    abstract protected function importCruise($cruise);

    /**
     * Импорт цен для круиза
     * Должен быть реализован в дочерних классах
     * @param int $checkinId ID заезда в MySQL
     * @param int $cruiseId ID круиза в SQLite
     * @param int $shipId ID теплохода в MySQL
     * @return bool true если цены успешно импортированы, false если цен нет
     */
    abstract protected function importPrices($checkinId, $cruiseId, $shipId);

    /**
     * Получение или создание категории кают с использованием ID источника
     * @param int|null $categoryId ID категории из источника
     * @param string $categoryName Название категории
     * @param int $shipId ID теплохода в MySQL
     * @param int|null $places Количество мест (null = не обновлять)
     * @return int ID категории кают в MySQL
     */
    protected function getCabinCategory($categoryId, $categoryName, $shipId, $places = null): int
    {
        // Используем обновленный метод getCabinCategoryId() с передачей ID
        return $this->riverCrs->getCabinCategoryId(
            $categoryName,
            $shipId,
            $this->edsCode,
            $places,
            $categoryId // Передаем ID источника
        );
    }

    /**
     * Сохранение цены в таблицу nprices через DeckPricesPatch
     * @param int $checkinId ID заезда
     * @param int $cabinId ID категории кают
     * @param int|null $deckId ID палубы (если есть)
     * @param int $price Цена
     * @param int $placesQnt Количество мест (по умолчанию 1)
     * @return bool true если цена сохранена, false если deckId отсутствует
     */
    protected function savePriceWithDeck($checkinId, $cabinId, $deckId, $price, $placesQnt = 1): bool
    {
        if (!$deckId) {
            return false;
        }

        try {
            pricePatch()->setPrice($checkinId, $deckId, $cabinId, $placesQnt, $price);
            ProcessLog::add("Сохранена цена в nprices для заезда $checkinId, каюта $cabinId, палуба $deckId: $price");
            return true;
        } catch (\Exception $e) {
            ProcessLog::add("Ошибка сохранения цены в nprices: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение или создание теплохода
     * @param string $shipName Название теплохода
     * @param int $sourceShipId ID теплохода в источнике
     * @return \Mcmraak\Rivercrs\Models\Motorships|null
     */
    protected function getMotorship($shipName, $sourceShipId)
    {
        return $this->riverCrs->getMotorship($shipName, $this->edsIdField, $sourceShipId);
    }

    /**
     * Получение или создание палубы
     * @param string $deckName Название палубы
     * @return \Mcmraak\Rivercrs\Models\Decks|null
     */
    protected function getDeck($deckName)
    {
        return $this->riverCrs->getDeck($deckName);
    }

    /**
     * Создание связи каюты с палубой
     * @param int $cabinId ID категории кают
     * @param int $deckId ID палубы
     */
    protected function deckPivotCheck($cabinId, $deckId)
    {
        $this->riverCrs->deckPivotCheck($cabinId, $deckId);
    }

    /**
     * Получение ID города
     * @param string $townName Название города
     * @return int ID города
     */
    protected function getTownId($townName)
    {
        return $this->riverCrs->getTownId($townName, $this->edsCode);
    }

    /**
     * Обновление времени последнего изменения заезда
     * @param int $checkinId ID заезда
     */
    protected function fixCheckin($checkinId)
    {
        $this->riverCrs->fixCheckin($checkinId);
    }

    /**
     * Получение или создание заезда
     * @param string $edsCruiseId ID круиза в источнике
     * @return Checkin
     */
    protected function getOrCreateCheckin($edsCruiseId): Checkin
    {
        $checkin = Checkin::where('eds_code', $this->edsCode)
            ->where('eds_id', $edsCruiseId)
            ->first();

        if (!$checkin) {
            $checkin = new Checkin;
            $checkin->eds_code = $this->edsCode;
            $checkin->eds_id = $edsCruiseId;
        }

        return $checkin;
    }

    /**
     * Очистка кеша заезда
     * @param int $checkinId ID заезда
     */
    protected function clearCheckinCache($checkinId)
    {
        $cabox = new \Zen\Cabox\Classes\Cabox('rivercrs');
        $cabox->del('rcrs:' . $checkinId);
        $cabox->del('exist_array:' . $checkinId);
    }

    /**
     * Пересоздание кеша заезда
     * @param int $checkinId ID заезда
     */
    protected function rebuildCheckinCache($checkinId)
    {
        Checkin::getResult($checkinId, true);
    }
}

