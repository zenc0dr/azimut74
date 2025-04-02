<?php namespace Mcmraak\Rivercrs\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Backend\Facades\Backend;
use Flash;
use Log;
use DB;
use Input;
use Zen\Excel\Classes\ImportXLS;
use Zen\Excel\Classes\ExportXLS;
use Carbon\Carbon;
use Exception;

class VolgaSettings extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-volga-uploads');
    }

    public function index() {

        #@TODO: Определенить время последней загрузки файла
        #$file1_time = temp_path().'volga_points.xls';
        #if(file_exists())


        return \View::make('mcmraak.rivercrs::volga.settings', [
            'backend_url' => Backend::url('mcmraak/rivercrs/volgasettings'),
        ]);
    }

    public function onUploadXls()
    {
        $files = Input::file();

        if(isset($files['file1'])) {
            $files['file1']->move(storage_path('app'), 'volga_points.xls');
        }

        if(isset($files['file2'])){
            $files['file2']->move(storage_path('app'), 'volga_points_pivot.xls');
        }

        Flash::success('Файлы загружены');
    }

    public static function readXls($filename)
    {

        return ImportXLS::fromFile($filename, 1);

        /*
        Excel::excel()->load(temp_path().'/'.$filename, function($xsl) use (&$return) {
            $return = $xsl->get();
        });
        return $return;
        */
    }

    public static function findTown($needle, $townsArr)
    {
        foreach ($townsArr as $item) {
            $item = trim($item);
            if(strpos($item, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function townFind($needle, $town)
    {
        $town = trim($town);
        if(strpos($town, $needle) !== false) {
            return true;
        }
    }

    # debug: /rivercrs/volga_debug?checkin=24775
    # debug: /rivercrs/volga_debug?checkin=24698
    # Рассматривается checkin_id=3331 , 3188
    #
    public static function getVolgaExcursion($checkin=null)
    {
        /*
            0 => "id"
            1 => "point_id"
            2 => "ship_id"
            3 => "check_in"
            4 => "check_out"
            5 => "duration"
            6 => "ship_code"
            7 => "is_excursion"
            8 => "excursion_link"
        */

        //try {
            $table_data = [];

            if(Input::get('checkin')) {
                $checkin = \Mcmraak\Rivercrs\Models\Checkins::find(intval(Input::get('checkin')));
            }

            # $checkin->waybill_count;

            $points = self::readXls('volga_points.xls');
            $pivot = self::readXls('volga_points_pivot.xls');

            unset($pivot[0]);

            $point_ids = false;

            foreach ($pivot as $row) {
                $cruise_id = intval($row[0]);
                if ($cruise_id == $checkin->eds_id) {
                    $point_ids = explode(',', $row[1]);
                    break;
                }
            }

            ### METHOD 1 ###


            if ($point_ids) {
                #DEBUG return false;

                foreach ($point_ids as $point_id) {
                    foreach ($points as $point) {
                        if (!$point[0]) continue;
                        if ($point[0] == 'id') continue;
                        $point_id = intval($point_id);
                        $id = intval($point[0]);
                        if ($point_id == $id) {
                            //dd($point_id);
                            $town = trim($point[1]); # Нижний Новгород


                            $check_in = ImportXLS::timeFormat($point[3], 'Carbon');
                            $check_out = ImportXLS::timeFormat($point[4], 'Carbon');

                            //dd($check_in, $check_out);


                            if ($check_out) {
                                $diff_in_days = $check_out->diffInDays($check_in);

                                if($point[5]) {
                                    $stay = ImportXLS::timeFormat($point[5], 'Carbon')->format('H:i');
                                } else {
                                    $stay = $check_out->diffInSeconds($check_in);
                                    $stay = gmdate('H:i', $stay);
                                }

                            } else {
                                $diff_in_days = null;
                                $check_out = null;
                                $stay = null;
                            }

                            self::addTableRaw($table_data, [
                                'diff_in_days' => $diff_in_days,
                                'check_in' => $check_in,
                                'check_out' => $check_out,
                                'town' => $town,
                                'stay' => $stay,
                            ]);
                            break;
                        }
                    }
                }
            } else {

                ### METHOD 2 ###
                $date_a = Carbon::parse($checkin->date);
                $date_b = Carbon::parse($checkin->dateb);
                $checkin_ship_volga_id = $checkin->motorship->volga_id;

                $w_towns = DB::table('mcmraak_rivercrs_waybills as waybills')
                    ->where('waybills.checkin_id', $checkin->id)
                    ->join(
                        'mcmraak_rivercrs_towns as towns',
                        'towns.id',
                        '=',
                        'waybills.town_id'
                    )
                    ->orderBy('waybills.order')
                    ->pluck('towns.name')
                    ->toArray();

                $first_town = $w_towns[0];
                $last_town = $w_towns[count($w_towns)-1];


                $point_id_first = null;
                $point_id_last = null;


                foreach ($points as $point) {

                    if (!$point[0]) continue;
                    if ($point[0] == 'id') continue;

                    $point_id = intval($point[0]);
                    $point_ship_id = $point[6];
                    $point_ship_id = intval($point_ship_id);
                    $town = trim($point[1]); # Нижний Новгород
                    $check_in = ImportXLS::timeFormat($point[3], 'Carbon');
                    $check_out = ImportXLS::timeFormat($point[4], 'Carbon');



                    # Сбор данных для анализа

                    # Соответсвие теплохода
                    #$ship_find = (strpos(mb_strtolower($ship_name), mb_strtolower($point_ship_name)) === false) ? false : true;
                    $ship_find = $point_ship_id == $checkin_ship_volga_id;

                    if(!$ship_find) continue; # Дальнейший анализ не требуется

                    $first_town_find = self::townFind($town, $first_town);
                    $last_town_find = self::townFind($town, $last_town);

                    if(!$first_town_find && !$last_town_find) continue; # Дальнейший анализ не требуется

                    ## Обозначение переменных ##
                    # $check_in - Прибытие точки
                    # $check_out - Отправление точки
                    # $date_a - Отправление заезда (начало всего заезда)
                    # $date_b - Прибытие заезда (окончание всего заезда)

                    # Обнуление времени
                    $check_in_z = $check_in;
                    $check_out_z = ($check_out)?$check_out:false;
                    $date_a_z = $date_a;
                    $date_b_z = $date_b;

                    $check_in_z->setTime(0, 0, 0);
                    if($check_out_z) $check_out_z->setTime(0, 0, 0);
                    $date_a_z->setTime(0, 0, 0);
                    $date_b_z->setTime(0, 0, 0);

                    # Начало диапазона
                    if(!$point_id_first && $check_out_z) {
                        if($first_town_find) {
                            if($check_out_z == $date_a_z) {
                                $point_id_first = $point_id;
                            }
                        }
                    }

                    # Конец диапазона
                    $modal_check = ($check_out_z)?:$check_in_z;
                    if($last_town_find) {
                        if($modal_check == $date_b_z) {
                            $point_id_last = $point_id;
                        }
                    }
                }

                if(!$point_id_first || !$point_id_last) return;


                #return 'NO';
                foreach ($points as $point) {

                    if ($point[0] == 'id') continue;
                    $point_id = intval($point[0]);
                    if($point_id >= $point_id_first && $point_id <= $point_id_last) {

                        $town = trim($point[1]); # Нижний Новгород
                        $check_in = ImportXLS::timeFormat($point[3], 'Carbon');
                        $check_out = ImportXLS::timeFormat($point[4], 'Carbon');

                        if ($check_out) {
                            $diff_in_days = $check_out->diffInDays($check_in);
                            if($point[5]) {
                                $stay = ImportXLS::timeFormat($point[5], 'Carbon')->format('H:i');
                            } else {
                                $stay = $check_out->diffInSeconds($check_in);
                                $stay = gmdate('H:i', $stay);
                            }

                        } else {
                            $diff_in_days = null;
                            $check_out = null;
                            $stay = null;
                        }

                        self::addTableRaw($table_data, [
                            'diff_in_days' => $diff_in_days,
                            'check_in' => $check_in,
                            'check_out' => $check_out,
                            'town' => $town,
                            'stay' => $stay,
                        ]);
                    }
                }
            }

            if(Input::get('checkin')) {
                dd($table_data);
            }

            return $table_data;
        /*}
        catch (Exception $ex) {
            $error = $ex->getMessage();
            Log::debug('VolgaSettings: '.$error);
            return [];
        }*/
    }

    public static function addTableRaw(&$table_data, $settings)
    {
        $diff_in_days = $settings['diff_in_days'];
        $check_in = $settings['check_in'];
        $check_out = $settings['check_out'];
        $town = $settings['town'];
        $stay = $settings['stay'];

        if ($diff_in_days == 0) {
            $table_data[] = [
                'date' => $check_in->format('d.m.Y'),
                'town' => $town,
                'arrival' => $check_in->format('H:i'),
                'stay' => $stay,
                'departure' => ($check_out)?$check_out->format('H:i'):'',
            ];
        } else {
            $table_data[] = [
                'date' => $check_in->format('d.m.Y'),
                'town' => $town,
                'arrival' => $check_in->format('H:i'),
                'stay' => $stay,
                'departure' => '',
            ];

            $table_data[] = [
                'date' => ($check_out)?$check_out->format('d.m.Y'):'',
                'town' => $town,
                'arrival' => '',
                'stay' => '',
                'departure' => ($check_out)?$check_out->format('H:i'):'',
            ];
        }
    }
}
