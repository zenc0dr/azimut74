<?php namespace Zen\Reviews\Models;

use Model;

class Binding extends Model
{
    public $table = 'zen_reviews_bindings';

    public $fillable = [
        'review_id',
        'entity_type',
        'entity_id',
    ];

    public $belongsTo = [
        'review' => [Review::class, 'key' => 'review_id'],
    ];
}
