<?php namespace Zen\History\Models;

use Model;

/**
 * Model
 */
class Link extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_history_links';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsTo = [
        'type' => LinkType::class,
    ];

    public $fillable = [
      'visiter_id',
      'url',
      'title',
      'type_id',
      'inner_id',
      'days',
      'transform_data'
    ];

    public function getTypeIdOptions(): array
    {
      $obItemList = LinkType::get();
      if ($obItemList->isEmpty()) {
        return [];
      }

      $arItemList = [];

      foreach ($obItemList as $obItem) {
        $arItemList[$obItem->id] = $obItem->name;
      }
      return $arItemList;
    }



}
