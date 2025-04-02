<?php namespace Zen\Dolphin\Traits;

use DB;

trait MultiGenerator
{
    function optionsMultiGenerator($model_class)
    {
        return $model_class::get()->pluck('name', 'id')->toArray();
    }

    function getMultiGenerator($pivot_table, $other_key)
    {
        return DB::table($pivot_table)->where($this->this_key, $this->id)->lists($other_key);
    }

    function setMultiGenerator($model_class, $value)
    {
        $this->{$model_class.'Dump'} = $value;
    }

    function saveMultiGenerator($options)
    {
        # Сохранять расширенные категории можно только из админки
        if(post('_token') === null) return;

        /* ############### Описание опции
        $options = [
            [
                'model' => '$model_class',
                'pivot' => '$pivot_table_name',
                'key'   => '$other_key',
            ]
        ];
        */

        foreach ($options as $option) {
            DB::table($option['pivot'])->where($this->this_key, $this->id)->delete();
            $dump = $this->{$option['model'].'Dump'};
            if(!$dump) continue;

            $insert = [];
            foreach ($dump as $id) {
                $insert[] = [
                    $this->this_key => $this->id,
                    $option['key'] => $id
                ];
            }

            DB::table($option['pivot'])->insert($insert);
        }

    }
}
