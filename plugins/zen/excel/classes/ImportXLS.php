<?php namespace Zen\Excel\Classes;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ImportXLS implements ToCollection
{
    public $data;
    public function collection(Collection $collection)
    {
        $this->data = $collection;
    }

    static function timeFormat($excelTime, $format = null)
    {
        $excelTime = (string) $excelTime;
        $excelTime = explode('.', (string) $excelTime);
        $days = (int) $excelTime[0];
        $days_in_seconds = ($days*86400) - 2208834000 - 345600; // 345600 странная корректировка

        $debug = false;

        if(@$excelTime[1]) {
            $ex_seconds = floatval('0.' . $excelTime[1]) * 86400;
        } else {
            $debug = true;
            $ex_seconds = 0;
        }

        $days_in_seconds += $ex_seconds+3600;
        $days_in_seconds = intval($days_in_seconds);
        #dd(date('d.m.Y', $days_in_seconds));
        if($format) {
            if($format=='Carbon') return Carbon::createFromTimestamp($days_in_seconds);
            return date($format, $days_in_seconds);
        } else {
            return $days_in_seconds;
        }
    }

    static function fromFile($fileName, $array = false)
    {
        $xls = new self;

        Excel::import($xls, $fileName);

        $data = $xls->data;

        if($array) {
            $array = [];
            foreach ($data as $row) {
                $new_row = [];
                foreach ($row as $col) {
                    $new_row[] = $col;
                }
                $array[] = $new_row;
            }
            return $array;
        }
        return $data;
    }
}
