<?php namespace Zen\Om\Models;

use Model;
use Zen\Om\Models\Category;

/**
 * Model
 */
class Store extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     *
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
    public $table = 'zen_om_stores';
    public $shop_request;


    public function beforeDelete()
    {
        if($this->count() < 2)
            throw new ApplicationException('You can not delete a single store!');
    }

    public function afterDelete()
    {
        $min_id = Store::min('id');
        Item::where('store_id', $this->id)->update(['store_id' => $min_id]);
    }

	public $hasMany = [
        'categories' => [
            'Zen\Om\Models\Category',
            'key' => 'store_id'
        ],
    ];

	public function rootItems()
	{
		return Category::where('store_id', $this->id)->where('parent_id',0)->get();
	}

	protected $jsonable = [
        'links',
    ];

}