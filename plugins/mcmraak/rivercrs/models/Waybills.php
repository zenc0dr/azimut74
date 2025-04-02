<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use DB;

/**
 * Model
 */
class Waybills extends Model
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
    public $table = 'mcmraak_rivercrs_waybills';


    /* Get path string eg: Town1->Town2->Town3 etc */
    public static function GetWaybillPath($checkin_id, $clean = false, $short = false, $separator = null)
    {
        $waybill = DB::table('mcmraak_rivercrs_waybills as way')
        ->where('way.checkin_id', $checkin_id)
        ->leftJoin(
            'mcmraak_rivercrs_towns as town',
            'town.id',
            '=',
            'way.town_id'
        )
        ->select(
            'town.name as name',
            'town.alt_name as alt_name',
            'way.bold as bold'
        )
        ->orderBy('way.order')
        ->get();

        $out = [];

        if ($short) {
            foreach ($waybill as $v) {
                if ($v->bold) {
                    $alt_name = trim($v->alt_name);
                    $name = trim($v->name);
                    $out[] = $alt_name ?: $name;
                }
            }
        }

        if (!$short || ($short && !$out)) {
            foreach ($waybill as $v) {
                if ($clean) {
                    $out[] = trim($v->name);
                } else {
                    $alt_name = trim($v->alt_name);
                    $name = trim($v->name);
                    $name = $alt_name ?: $name;
                    if ($short) {
                        $out[] = $alt_name ?: $name;
                    } else {
                        $out[] = ($v->bold)?"<strong>$name</strong>":$name;
                    }
                }
            }
        }

        if (!$separator) {
            $separator = ' > ';
        }

        return join($separator, $out);
    }
}
