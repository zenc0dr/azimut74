<?php namespace Mcmraak\Rivercrs\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Flash;
use October\Rain\Exception\ApplicationException;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use DB;
use Mcmraak\Rivercrs\Models\Backup;
use Mcmraak\Rivercrs\Classes\Dbh;
use Log;
use Mcmraak\Rivercrs\Classes\CacheSettings;

class Cabins extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = ['mcmraak.rivercrs'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-cabins');
    }

    public static function cabinModal($cabin_id)
    {
        $place_names = [
            1 => 'Одно',
            2 => 'Двух',
            3 => 'Трёх',
            4 => 'Четырёх',
            5 => 'Пяти',
            6 => 'Шести',
            7 => 'Семи',
            8 => 'Восьми',
            9 => 'Девяти',
            10 => 'Десяти',
        ];

        $cabin = \Mcmraak\Rivercrs\Models\Cabins::find($cabin_id);
        return \View::make(
            'mcmraak.rivercrs::cabin_modal',
            [
                'cabin'=>$cabin,
                'placenames'=>$place_names,
            ]
        );
    }

    public function onMerge()
    {
        $input = Input::all();
        $this_cabin_id = $this->params[0];
        $merge_cabin_id = $input['Cabins']['merger']; # Родитель который не меняется
        if(!$merge_cabin_id) {
            Flash::error('Не выбрана каюта для слияния!');
            return;
        }

        $this_cabin = Cabin::find($this_cabin_id);

        if(!$this_cabin) {
            throw new ApplicationException('Каюты слиты');
        }

        $merge_cabin = Cabin::find($merge_cabin_id);

        $alert_message = 'Нельзя выполнить слияние, каюта назначения пренадлежит этому же источнику';

        /*
        if($merge_cabin->waterway_name && $this_cabin->waterway_name) {
            throw new ApplicationException($alert_message);
        }
        if($merge_cabin->volga_name && $this_cabin->volga_name) {
            throw new ApplicationException($alert_message);
        }
        if($merge_cabin->gama_name && $this_cabin->gama_name) {
            throw new ApplicationException($alert_message);
        }
        if($merge_cabin->germes_name && $this_cabin->germes_name) {
            throw new ApplicationException($alert_message);
        }
        */

        $change_name = false;
        $eds_code = null;

        $eds_names = CacheSettings::get('eds_names');
        foreach ($eds_names as $item) {
            $eds_code = $item['eds_code'];
            if ($merge_cabin->{$eds_code.'_name'} && $this_cabin->{$eds_code.'_name'}) {
                //throw new ApplicationException($alert_message);
                $change_name = true;
                break;
            }
        }

        # Создание бекапа
        $backup = new Backup;
        $backup->name = "Слияние классов кают: [{$this_cabin->id}] '{$this_cabin->category}' >>> [{$merge_cabin->id}] '{$merge_cabin->category}'";
        $backup->tables = [
            'mcmraak_rivercrs_cabins',
            'mcmraak_rivercrs_booking',
            'mcmraak_rivercrs_decks_pivot',
            'mcmraak_rivercrs_incabin_pivot',
            'mcmraak_rivercrs_pricing',
        ];
        $backup->save();

        if ($change_name) {
            # Поменять у родителя $eds_name на новый - этого источника
            DB::table('mcmraak_rivercrs_cabins')
                ->where('id', $merge_cabin_id)
                ->update([
                    $eds_code.'_name' => $this_cabin->category
                ]);
            DB::table('mcmraak_rivercrs_pricing')
                ->where('cabin_id', $this_cabin_id)
                ->update([
                    'cabin_id' => $merge_cabin_id
                ]);
        }
        else {
            $update_arr = [];
            foreach ($eds_names as $item) {
                $eds_code = $item['eds_code'];
                $update_arr[$eds_code.'_name'] = $this_cabin->{$eds_code.'_name'};
            }

            DB::table('mcmraak_rivercrs_cabins')
                ->where('id', $merge_cabin->id)
                ->update($update_arr);
        }



        # Json field replace
        $old_like = '"cabin_id":"'.$this_cabin->id.'"';
        $new_like = '"cabin_id":"'.$merge_cabin->id.'"';
        $booking = DB::table('mcmraak_rivercrs_booking')
            ->where('cabins', 'like', "%$old_like%")
            ->get();

        foreach($booking as $item)
        {
            $cabins = str_replace($old_like, $new_like, $item->cabins);
            DB::table('mcmraak_rivercrs_booking')->where('id', $item->id)
                ->update([
                    'cabins' => $cabins
                ]);
        }

        # Связь с палубой
//        DB::table('mcmraak_rivercrs_decks_pivot')
//            ->where('cabin_id', $this_cabin->id)
//            ->update([
//                'cabin_id' => $merge_cabin->id
//            ]);

        Dbh::removePivotDublicates('mcmraak_rivercrs_decks_pivot');

        DB::table('mcmraak_rivercrs_incabin_pivot')
            ->where('cabin_id', $this_cabin->id)
            ->update([
                'cabin_id' => $merge_cabin->id
            ]);

        Dbh::removePivotDublicates('mcmraak_rivercrs_incabin_pivot');

        DB::table('mcmraak_rivercrs_pricing')
            ->where('cabin_id', $this_cabin->id)
            ->update([
                'cabin_id' => $merge_cabin->id
            ]);

        Dbh::removePivotDublicates('mcmraak_rivercrs_pricing');

        $this_cabin->delete();

        Flash::success("Объединение [{$this_cabin->id}]{$this_cabin->category} => [{$merge_cabin->id}]{$merge_cabin->category} завершено.");
    }
}
