<?php namespace Mcmraak\Blocks\Models;

use Model;
use DB;

/**
 * Model
 */
class Map extends Model
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
    public $table = 'mcmraak_blocks_maps';

    public $belongsToMany = [
        'markers' => [
            'Mcmraak\Blocks\Models\Marker',
            'table'    => 'mcmraak_blocks_maps_markers',
            'key'      => 'map_id',
            'otherKey' => 'marker_id'
        ]
    ];



    public $markersDump;

    public function getMarkersOptions()
    {
        return Marker::lists('name', 'id');
    }
    public function getMapsMarkersAttribute()
    {
        $records = DB::table('mcmraak_blocks_maps_markers')->
        where('map_id',$this->id)->
        get();

        $options = [];
        foreach ($records as $record)
        {
            $options[] = ['markers' => $record->marker_id];
        }
        return $options;
    }
    public function setMapsMarkersAttribute($value)
    {
        $this->markersDump = $value;
    }
    public function saveMapsMarkers()
    {
        DB::table('mcmraak_blocks_maps_markers')->
            where('map_id',$this->id)->
            delete();
        $options = [];
        if($this->markersDump)
        {
            foreach ($this->markersDump as $value)
            {
                $options[] = [
                    'map_id' => $this->id,
                    'marker_id' => $value['markers'],
                ];
            }
            DB::table('mcmraak_blocks_maps_markers')->insert($options);
        }
    }

    public function afterSave()
    {
        $this->saveMapsMarkers();
    }

    public function afterDelete()
    {
        DB::table('mcmraak_blocks_maps_markers')->
        where('map_id',$this->id)->
        delete();
    }

}