<?php namespace Mcmraak\Sans\Models;

use Model;

/**
 * Model
 */
class Hotel extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $fillable = [
        'id',
        'cid',
        'name',
        'resort_id',
        'hotel_category_id',
        'short_name',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_hotels';

    public $belongsTo = [
        'resort' => [
            'Mcmraak\Sans\Models\Resort',
            'key' => 'resort_id',
        ],
        'category' => [
            'Mcmraak\Sans\Models\HotelCategory',
            'key' => 'hotel_category_id',
        ],
    ];

    public function scopeFilterCategories($q, $id)
    {
        return $q->whereHas('category', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }

    public function scopeFilterResorts($q, $id)
    {
        return $q->whereHas('resort', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }

    public function getBagAttribute(){
        return \Mcmraak\Sans\Controllers\Parser::hotelProfile($this->id,true);
    }

    public function reviews()
    {
        return Review::where('hotel_id', $this->id)
            ->where('active', 1)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function rating()
    {
        $items = Review::where('hotel_id', $this->id)->where('active', 1);
        $count = $items->count();
        if(!$count) return 5;
        $sum = $items->sum('stars');
        return round( $sum / $count, 1, PHP_ROUND_HALF_EVEN);
    }

    public function reviewsCount()
    {
        $count = Review::where('hotel_id', $this->id)->where('active', 1)->count();
        return  $count . ' ' . $this->incline(['отзыв','отзыва','отзывов'], $count);
    }

    public function incline($words,$n){
        if($n%100>4 && $n%100<20){
            return $words[2];
        }
        $a = array(2,0,1,1,1,2);
        return $words[$a[min($n%10,5)]];
    }

}
