<?php namespace Zen\Reviews\Models;

use Model;
use System\Models\File;

class ReviewPhoto extends Model
{
    public $table = 'zen_reviews_photos';

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public $belongsTo = [
        'file' => [File::class, 'key' => 'system_file_id'],
        'review' => [Review::class, 'key' => 'review_id'],
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
