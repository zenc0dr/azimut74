<?php namespace Zen\Fetcher\Models;

use Model;
use October\Rain\Database\Traits\Validation;
use Zen\Fetcher\Classes\Services\PoolManager;


/**
 * @property string $code - Уникальный идентификатор пула
 * @method static active()
 */
class Pool extends Model
{
    use Validation;

    public $timestamps = false;
    public $incrementing = false;
    public $keyType = 'string';
    public $primaryKey = 'code';
    public $table = 'zen_fetcher_pools';
    public $rules = [
        'code' => 'required|unique:zen_fetcher_pools'
    ];

    private ?array $scenario_dump = null;
    private ?array $pool_settings_dump = null;

    private function getPath(string $file = null): ?string
    {
        if (!$this->code) {
            return null;
        }

        if ($file) {
            return storage_path("fetcher/$this->code/$file.json");
        } else {
            return storage_path("fetcher/$this->code");
        }
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function getManagerAttribute(): PoolManager
    {
        return new PoolManager($this);
    }

    public function setScenarioAttribute(array $value = null)
    {
        if ($value !== null) {
            $this->scenario_dump = $value;
        }
    }

    public function getScenarioAttribute()
    {
        if (file_exists($this->getPath('scenario'))) {
            return fetcher()->files()->arrayFromFile($this->getPath('scenario'));
        }
        return null;
    }

    public function setPoolSettingsAttribute(array $value = null)
    {
        if ($value !== null) {
            $this->pool_settings_dump = collect($value)->map(function ($record) {
                $record['pool_settings_data'] = fetcher()->fromJson($record['pool_settings_data']);
                return $record;
            })->toArray();
        }
    }

    public function getPoolSettingsAttribute()
    {
        if (file_exists($this->getPath('settings'))) {
            $value = fetcher()->files()->arrayFromFile($this->getPath('settings'));
            return collect($value)->map(function ($record) {
                $record['pool_settings_data'] = fetcher()
                    ->toJson(
                        $record['pool_settings_data'],
                        true,
                        true
                    );
                return $record;
            })->toArray();
        }
        return null;
    }

    private function saveScenario()
    {
        if ($this->scenario_dump !== null) {
            fetcher()->files()->arrayToFile($this->scenario_dump, $this->getPath('scenario'));
        }
    }

    private function savePoolSettings()
    {
        if ($this->pool_settings_dump !== null) {
            fetcher()->files()->arrayToFile($this->pool_settings_dump, $this->getPath('settings'));
        }
    }

    public function afterSave()
    {
        $this->saveScenario();
        $this->savePoolSettings();
    }

    public function afterDelete()
    {
        fetcher()->files()->deleteDir($this->getPath());
    }
}
