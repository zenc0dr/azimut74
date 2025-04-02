<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use October\Rain\Exception\ApplicationException;
use DB;
/**
 * Model
 * @method static find(int $category_id)
 */
class Cabins extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation test
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_cabins';

    public $incabinDump;
    public $decksDump;
    //public $thisId; // for detect id and model method (create or update)

    # Relationships
    public function price($checkin_id)
    {
        return DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $checkin_id)
            ->where('cabin_id', $this->id)
            ->first();
    }

    public $belongsTo = [
		'motorship' => ['Mcmraak\Rivercrs\Models\Motorships', 'key' => 'motorship_id'],
        'bed' => ['Mcmraak\Rivercrs\Models\Beds', 'key' => 'bed_id'],
        'comfort' => ['Mcmraak\Rivercrs\Models\Comforts', 'key' => 'comfort_id'],
	];
    public $attachMany = [
        'images' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
    ];

    public $belongsToMany = [
        'decks_list' => [
            'Mcmraak\Rivercrs\Models\Decks',
            'table'    => 'mcmraak_rivercrs_decks_pivot',
            'key'      => 'cabin_id',
            'otherKey' => 'deck_id',
            'order' => 'sort_order'
        ],
        'incabin_list' => [
            'Mcmraak\Rivercrs\Models\Incabin',
            'table'    => 'mcmraak_rivercrs_incabin_pivot',
            'key'      => 'cabin_id',
            'otherKey' => 'incabin_id'
        ],
    ];


    # for filter of motorshis in cabins field list
    public function scopeFilterMotorships($query, $motorship)
    {
        return $query->whereHas('motorship', function($q) use ($motorship) {
            $q->whereIn('id', $motorship);
        });
    }

    public function setMergerAttribute()
    {
        return [];
    }

    public function getMergerOptions()
    {
        if(!$this->id) return [];
        $cabins = $this->motorship->cabinslist()
            ->where('id', '<>', $this->id)
        ->get();
        $return = [];
        foreach ($cabins as $cabin)
        {
            $eds = [];
            if($cabin->waterway_name) $eds[] = 'Водоход';
            if($cabin->volga_name) $eds[] = 'Волга';
            if($cabin->gama_name) $eds[] = 'Гама';
            if($cabin->germes_name) $eds[] = 'Гермес';
            $eds = join(', ', $eds);
            if($eds) $eds = " Источники: [ $eds ]";

            $return[$cabin->id] = $cabin->category.$eds." [ id:{$cabin->id} ]";
        }
        return [0 => '-- '] + $return;
    }

    # Get list for dropdown of motorships field in cabin form
    public function getMotorshipOptions()
    {
        return Motorships::lists('name', 'id');
    }

    # Save value from dropdown of motorships field in cabin form
    public function setMotorshipAttribute($value)
    {
        $this->motorship_id = $value;
    }

    public function placeName(){
        if(!$this->places_main_count) return '';
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
        return $place_names[$this->places_main_count] .'местная каюта';
    }

    public function setPlacesMainCountAttribute($value){
        if($value < 1) throw new ApplicationException('Количество основных мест не может быть меньше 1');
        $this->attributes['places_main_count'] = $value;
    }

    # Get list for dropdown of beds field in cabin form
    public function getBedIdOptions()
    {
        return ['0' => 'Не указано'] + Beds::lists('name', 'id');
    }

    # Save value from dropdown of beds in cabin form
    public function setBedIdAttribute($value)
    {
        if(!$value) $value = 0;
        $this->attributes['bed_id'] = $value;
    }

    # Get list for dropdown of comfort field in cabin form
    public function getComfortIdOptions()
    {
        return ['0' => 'Не указано'] + Comforts::lists('name', 'id');
    }

    # Save value from dropdown of beds in cabin form
    public function setComfortIdAttribute($value)
    {
        if(!$value) $value = 0;
        $this->attributes['comfort_id'] = $value;
    }

    # Get list of possible options for "In cabin"
    public function getIncabinOptions()
    {
        return Incabin::orderBy('id')->lists('name', 'id');
    }

    # Get checked values to "In cabin" checkboxlist
    public function getIncabinAttribute ()
    {
        return IncabinPivot::where('cabin_id', $this->id)
                             //->orderBy('incabin_id')
                             ->lists('incabin_id');
    }

    # Get list of possible decks
    public function getDecksOptions()
    {
        return Decks::orderBy('sort_order')->where('parent_id', 0)->lists('name', 'id');
    }

    # Get checked values to decks checkboxlist
    public function getDecksAttribute ()
    {
        return DecksPivot::where('cabin_id', $this->id)
                             //->orderBy('deck_id')
                             ->lists('deck_id');
    }

     # Motorships "In cabin" mutator
    public function setIncabinAttribute($value)
    {
        //$this->thisId = $this->id;
        $this->incabinDump = $value;
    }

    public function saveIncabin($id)
    {
        if($this->incabinDump){
            IncabinPivot::where('cabin_id', $id)->delete();
            $arr = [];
            foreach ($this->incabinDump as $value) {
                $arr[] =
                    [
                        'cabin_id' => $id,
                        'incabin_id' => $value,
                    ];
            }
            IncabinPivot::insert($arr);
        }
    }

     # Motorships "Decks" mutator
    public function setDecksAttribute($value)
    {
        //$this->thisId = $this->id;
        $this->decksDump = $value;
    }

    public function saveDecks($id)
    {
        DecksPivot::where('cabin_id', $id)->delete();
        if($this->decksDump){
            $arr = [];
            foreach ($this->decksDump as $value) {
                $arr[] =
                    [
                        'cabin_id' => $id,
                        'deck_id' => $value,
                    ];
            }
            DecksPivot::insert($arr);
        }
    }

    # Checking the name of the cabin category for emptiness
    public function beforeSave()
    {
        if(!$this->category)
        throw new ApplicationException('Введите название категории каюты!');
    }

    # Motorships after save event (for ID)
    public function afterSave()
    {
        $this->saveIncabin($this->id);
        $this->saveDecks($this->id);
    }

    public function afterDelete()
    {
         IncabinPivot::where('cabin_id', $this->id)->delete();
         Pricing::where('cabin_id', $this->id)->delete();
    }

    public function QQ($checkin, $deck, $cabinRenderData)
    {
        //dd($cabinRenderData, $this->places_main_count);

        require base_path('plugins/mcmraak/rivercrs/classes/qqsettings/data_arr.php');
        return $arr;
        //return json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    public function renderRoomType($price)
    {
        $decks = join(', ', $this->decks_list->pluck('name')->toArray());
        return \View::make('mcmraak.rivercrs::qq.roomType', ['decks' => $decks, 'cabin' => $this, 'price' => $price])->render();
    }

    function getImagesArrAttribute()
    {
        $output = [];
        foreach ($this->images as $image) {
            $output[] = $image->path;
        }
        return $output;
    }

}
