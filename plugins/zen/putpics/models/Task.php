<?php namespace Zen\Putpics\Models;

use Model;
use October\Rain\Exception\ApplicationException;

/**
 * Model
 */
class Task extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_putpics_tasks';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $model_item;


    public function getTitleAttribute()
    {
        $model_item = $this->model_item;

        if ($this->attachment_type == 'Mcmraak\Rivercrs\Models\Cabins') {
            return "Каюта RiverCRS: $model_item->category для теплохода {$model_item->motorship->name}";
        }

        if ($this->attachment_type == 'Mcmraak\Rivercrs\Models\Kpage') {
            return "Cтраница pro-kruiz: $model_item->name";
        }

        if ($this->attachment_type == 'Mcmraak\Rivercrs\Models\Motorships') {
            return "Теплоход RiverCRS: $model_item->name";
        }

        if ($this->attachment_type == 'Srw\Catalog\Models\Items') {
            return "Горящий тур: $model_item->name";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\City') {
            return "Город Dolphin: $model_item->name";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\Region') {
            return "Регион Dolphin: $model_item->name";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\Hotel') {
            return "Отель Dolphin: $model_item->name";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\Page') {
            return "Страница Dolphin: $model_item->name";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\Tour') {
            return "Тур Dolphin: $model_item->name";
        }


        throw new ApplicationException($this->attachment_type);
    }

    public function getBackendUrlAttribute()
    {
        if ($this->attachment_type == 'Mcmraak\Rivercrs\Models\Cabins') {
            return "/console/mcmraak/rivercrs/cabins/update/$this->attachment_id#primarytab-opisanie";
        }

        if ($this->attachment_type == 'Mcmraak\Rivercrs\Models\Kpage') {
            return "/console/mcmraak/rivercrs/kpages/update/$this->attachment_id";
        }

        if ($this->attachment_type == 'Mcmraak\Rivercrs\Models\Motorships') {
            return "/console/mcmraak/rivercrs/motorships/update/$this->attachment_id";
        }

        if ($this->attachment_type == 'Srw\Catalog\Models\Items') {
            return "/console/srw/catalog/items/update/$this->attachment_id#primarytab-opisanie";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\City') {
            return "/console/zen/dolphin/cities/update/$this->attachment_id";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\Region') {
            return "/console/zen/dolphin/regions/update/$this->attachment_id";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\Hotel') {
            return "/console/zen/dolphin/hotels/update/$this->attachment_id#primarytab-lokalnye-izobrazheniya";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\Page') {
            return "/console/zen/dolphin/pages/update/$this->attachment_id#primarytab-osnovnoe";
        }

        if ($this->attachment_type == 'Zen\Dolphin\Models\Tour') {
            return "/console/zen/dolphin/tours/update/$this->attachment_id#primarytab-media";
        }

        throw new ApplicationException($this->attachment_type);
    }

    public function getExtraAttribute()
    {
        $model_item = $this->model_item;
        if ($this->attachment_type == 'Zen\Dolphin\Models\Hotel') {
            return view('zen.putpics::xtra', ['record' => $model_item]);
        }
    }

    public function afterFetch()
    {
        $this->model_item = app($this->attachment_type)->find($this->attachment_id);
    }
}
