<?php namespace Zen\Worker\Classes;

trait TargetedParseFilter
{
    /** @var int[] */
    protected $onlyCruiseIds = [];

    /** @var int[] */
    protected $onlyShipIds = [];

    public function setTargetFilters(array $cruiseIds = [], array $shipIds = [])
    {
        $this->onlyCruiseIds = array_values(array_unique(array_map('intval', $cruiseIds)));
        $this->onlyShipIds = array_values(array_unique(array_map('intval', $shipIds)));
        return $this;
    }

    public function isTargeted(): bool
    {
        return $this->onlyCruiseIds !== [] || $this->onlyShipIds !== [];
    }

    protected function allowsCruise($cruiseId, $shipId = null): bool
    {
        if (!$this->isTargeted()) {
            return true;
        }
        $cruiseId = (int) $cruiseId;
        if ($this->onlyCruiseIds && !in_array($cruiseId, $this->onlyCruiseIds, true)) {
            return false;
        }
        if ($this->onlyShipIds && $shipId !== null && $shipId !== '' && !in_array((int) $shipId, $this->onlyShipIds, true)) {
            return false;
        }
        return true;
    }
}
