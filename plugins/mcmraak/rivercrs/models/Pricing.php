<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use DB;
use Log;
use Exception;
use BackendAuth;
use Zen\Excel\Classes\ExportXLS;
use Zen\Excel\Classes\ImportXLS;

/**
 * Model
 */
class Pricing extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_pricing';

    # JSON Builder of pricing pivot data
    public static function Pivot($checkin_id, $motorship_id)
    {
        if(!BackendAuth::check()) return 'Access error';
        $price = DB::table('mcmraak_rivercrs_cabins')
        ->where('mcmraak_rivercrs_cabins.motorship_id', $motorship_id)
        ->leftJoin('mcmraak_rivercrs_pricing', function($join) use ($checkin_id){
            $join->on('mcmraak_rivercrs_pricing.cabin_id', '=', 'mcmraak_rivercrs_cabins.id');
            if($checkin_id) {
                $join->on('mcmraak_rivercrs_pricing.checkin_id', '=', DB::raw($checkin_id));
            } else {
                $join->on('mcmraak_rivercrs_pricing.checkin_id', '=', DB::raw(0));
            }
        })
        ->select(
            'mcmraak_rivercrs_cabins.id as cabin_id',
            'mcmraak_rivercrs_cabins.category as cabin_name',
            'mcmraak_rivercrs_pricing.price_a as price_a',
            'mcmraak_rivercrs_pricing.price_b as price_b'
        )
        ->orderBy('mcmraak_rivercrs_cabins.id')
        ->get();

      return json_encode ($price, JSON_UNESCAPED_UNICODE);
    }

    public static function CheckinPrice($checkin_id, $motorship_id)
    {
        return DB::table('mcmraak_rivercrs_cabins')
            ->where('mcmraak_rivercrs_cabins.motorship_id', $motorship_id)
            ->where('price_a', '>', 0) # Чтобы в заезд не попадали нулевые цены
            ->leftJoin('mcmraak_rivercrs_pricing', function($join) use ($checkin_id){
                $join->on('mcmraak_rivercrs_pricing.cabin_id', '=', 'mcmraak_rivercrs_cabins.id');
                if($checkin_id) {
                    $join->on('mcmraak_rivercrs_pricing.checkin_id', '=', DB::raw($checkin_id));
                } else {
                    $join->on('mcmraak_rivercrs_pricing.checkin_id', '=', DB::raw(0));
                }
            })
            ->select(
                'mcmraak_rivercrs_cabins.id as cabin_id',
                'mcmraak_rivercrs_cabins.category as cabin_name',
                'mcmraak_rivercrs_pricing.price_a as price_a',
                'mcmraak_rivercrs_pricing.price_b as price_b'
            )
            ->orderBy('mcmraak_rivercrs_cabins.order')
            ->get();
    }

    # Array builder of motorship price
    public static function MotorshipPriceArr($motorship_id)
    {
        $checkins = Checkins::where('motorship_id',$motorship_id)->get();
        if(!count($checkins)) return;
        $result = [];
           foreach($checkins as $checkin)
           {
                $records = DB::table('mcmraak_rivercrs_pricing')
                ->where('checkin_id',$checkin->id)
                ->leftJoin(
                    'mcmraak_rivercrs_cabins',
                    'mcmraak_rivercrs_cabins.id',
                    '=',
                    'mcmraak_rivercrs_pricing.cabin_id'
                    )
                ->select(
                    'mcmraak_rivercrs_pricing.checkin_id as checkin_id',
                    'mcmraak_rivercrs_pricing.cabin_id as cabin_id',
                    'mcmraak_rivercrs_cabins.category as cabin_name',
                    'mcmraak_rivercrs_pricing.price_a as price_a',
                    'mcmraak_rivercrs_pricing.price_b as price_b'
                    )
                ->orderBy('mcmraak_rivercrs_cabins.id')
                ->get();

                $waybill = Waybills::GetWaybillPath($checkin->id);

                $result[] = [
                    'checkin_id' => $checkin->id,
                    'waybill' => $waybill,
                    'records' => $records
                ];
            }
            return $result;
    }

    /**
    * JSON Builder of motorship price
    * @return json
    */
    public static function Motorship($motorship_id)
    {
        if(!BackendAuth::check()) return 'Access error';
        $result = self::MotorshipPriceArr($motorship_id);
        if(!$result) return 'null';
        return json_encode ($result, JSON_UNESCAPED_UNICODE);
    }

    public static function getTownsOfMotorsip($motorship_id)
    {
        if(!BackendAuth::check()) return 'Access error';
        $return = \DB::table('mcmraak_rivercrs_checkins as checkins')
            ->where('motorship_id', $motorship_id)
            ->join('mcmraak_rivercrs_waybills as waybills',
                function($query){
                    $query->on('waybills.checkin_id','=','checkins.id');
                    $query->where('waybills.order', 0);
                }
            )
            ->join(
                'mcmraak_rivercrs_towns as towns',
                'waybills.town_id',
                '=',
                'towns.id'
            )
            ->select(
                'towns.id as id',
                'towns.name as name'
            )
            ->distinct('towns.name')
            ->get();
        return json_encode ($return, JSON_UNESCAPED_UNICODE);
    }

    /*'waybills.checkin_id',
                '=',
                'checkins.id'*/

    /**
    * Return price of one ship.
    * @return array
    * ex: http://azimut.dc/rivercrs/api/v1/pricing/price/12/61
    */
    public static function PriceXLS($motorship_id, $town_id)
    {
        if(!BackendAuth::check()) return 'Access error';

        if($town_id){
            $checkins = \DB::table('mcmraak_rivercrs_checkins as checkins')
                ->where('motorship_id', $motorship_id)
                ->join('mcmraak_rivercrs_waybills as waybills',
                    function($query) use ($town_id) {
                        $query->on('waybills.checkin_id','=','checkins.id');
                        $query->where('waybills.order', 0);
                        $query->where('waybills.town_id', $town_id);
                    }
                )
                ->select('checkins.*')
                ->orderBy('checkins.date','asc')
                ->get();
        } else {
            $checkins = Checkins::where('motorship_id', $motorship_id)->
            orderBy('date','asc')->
            get();
        }

        # Получаем все кабины теплохода с указанным id
        $cabins = Cabins::where('motorship_id', $motorship_id)
            ->orderBy('order')
            ->get();

        $cabins_orders = [];
        foreach ($cabins as $cabin)
        {
            $cabins_orders[$cabin->order] = $cabin->id;
        }

        # Формируем первые 5 заголовков столбцов
        $titles = [
            'id Заезда: ',
            'Отправление',
            'Прибытие',
            'Дней',
            'Маршрут',
        ];

        # Добавляем к загаловкам название кают вида "[32] 1А (1 мест) шлюп"
        foreach($cabins as $cabin){
            $titles[] = "[{$cabin->id}] {$cabin->category}";
        }

        # Перебираем заезды
        $rown = [];
        foreach($checkins as $checkin){
            # Получаем "Отправление"
            $date = $checkin->date;
            $date = strtotime($date);
            $date = date('d-m-Y',$date);

            # Получаем "Прибытие"
            $dateb = $checkin->dateb;
            $dateb = strtotime($dateb);
            $dateb = date('d-m-Y',$dateb);

            # Формируем ячейки строки
            $cols = [
                $checkin->id, // id Заезда:
                $date, // Отправление
                $dateb, // Прибытие
                $checkin->days, // Дней
                Waybills::GetWaybillPath($checkin->id, true, true), // Маршрут
            ];

            # Получаем цены на каюты из этого заезда
            $prices = Pricing::where('checkin_id', $checkin->id)->
            orderBy('cabin_id')-> # issue1: Формируется по id
            get();

            $prices = self::trueOrdering($prices, $cabins_orders);

            # Вот тут Небходимо после этого восстановить порядок из кабин

            # Дополняем ячейки ценами кают
            foreach($prices as $price){
                $cols[] = $price['price_a'];
            }

            $rows[] = $cols;
        }

        return ExportXLS::download($titles, $rows, "ship_id[$motorship_id].xls");
    }

    public static function trueOrdering($prices, $ordering)
    {
        $prices_arr = [];
        foreach ($prices as $price){
            $prices_arr[$price->cabin_id] = $price->price_a;
        }
        $return = [];
        foreach ($ordering as $item)
        {
            $return[] = [
                'price_a' => (isset($prices_arr[$item]))?$prices_arr[$item]:0
            ];
        }
        return $return;
    }

    /**
    * Upload XLS price.
    * @return void
    */
    Public static function UploadXLS($price)
    {
        if(!BackendAuth::check()) return 'Access error';
        if(!$price) return 'Не выбран файл!';

        $price[0]->move(storage_path('app'),'uploadprice.xls');

        //dd(base_path() . '/storage/temp/uploadprice.xls');

        $data = ImportXLS::fromFile('uploadprice.xls', 1);

        $cabin_ids = self::getCabinIds($data[0]);
        unset($data[0]);

        $iserts = 0;
        $updates = 0;
        foreach ($data as $row)
        {
            $checkin_id = (int) $row[0];
            array_splice($row, 0, 5);
            $i = 0;
            foreach ($row as $cabin_price) {
                $cabin_id = $cabin_ids[$i];
                $cabin_price = intval($cabin_price);
                $price_exist = Pricing::where('checkin_id', $checkin_id)
                    ->where('cabin_id', $cabin_id)
                    ->count();
                if($price_exist)
                {
                    Pricing::where('checkin_id',$checkin_id)
                        ->where('cabin_id', $cabin_id)
                        ->update([
                            'price_a' => $cabin_price
                        ]);
                    $updates++;
                } else {
                    $price = new Pricing;
                    $price->checkin_id = $checkin_id;
                    $price->cabin_id = $cabin_id;
                    $price->price_a = $cabin_price;
                    $price->save();
                    $iserts++;
                }
                $i++;
            }
            $processed = $updates + $iserts;
        }
        echo "Загрузка завершена: ячеек обработано [$processed], новых [$iserts]";

        /*
        Excel::excel()->load(base_path() . '/storage/temp/uploadprice.xls', function($xsl) {

            $data = $xsl->get();
            $iserts = 0;
            $updates = 0;
            foreach ($data as $row)
            {
                $arr = $row->toArray();
                $checkin_id = (int) $arr['id_zaezda'];

                array_splice($arr, 0, 5);

                foreach ($arr as $cabin_id => $price_a){
                    preg_match("/^(\d+)_/", $cabin_id, $out);
                    $cabin_id = $out[1];
                    $price_a = (int) $price_a;
                    $price_exist = Pricing::where('checkin_id',$checkin_id)->
                        where('cabin_id', $cabin_id)->count();
                    if($price_exist)
                    {
                        $price = Pricing::where('checkin_id',$checkin_id)->
                            where('cabin_id', $cabin_id)->
                            update([
                                'price_a' => $price_a
                        ]);
                        $updates++;
                    } else {
                        $price = new Pricing;
                        $price->checkin_id = $checkin_id;
                        $price->cabin_id = $cabin_id;
                        $price->price_a = $price_a;
                        $price->save();
                        $iserts ++;
                    }
                }
                $processed = $updates + $iserts;
            }
            echo "Загрузка завершена: ячеек обработано [$processed], новых [$iserts]";
        });
        */
    }

    public static function getCabinIds($titles)
    {
        $ids = [];
        array_splice($titles, 0, 5);
        foreach ($titles as $title) {
            preg_match("/^\[(\d+)\]/", $title, $out);
            $ids[] = intval($out[1]);
        }
        return $ids;
    }

}
