<?php namespace Zen\Reviews\Classes;

use Backend;
use System\Models\File;
use Zen\Reviews\Models\Review;
use Zen\Reviews\Models\ReviewPhoto;

class ReviewPhotoService
{
    public function ensureMeta(File $file, Review $review, bool $isPublished = false): ReviewPhoto
    {
        $meta = ReviewPhoto::query()
            ->where('system_file_id', (int) $file->id)
            ->first();

        if ($meta) {
            if ((int) $meta->review_id !== (int) $review->id) {
                $meta->review_id = (int) $review->id;
                $meta->save();
            }

            return $meta;
        }

        $meta = new ReviewPhoto();
        $meta->system_file_id = (int) $file->id;
        $meta->review_id = (int) $review->id;
        $meta->is_published = $isPublished;
        $meta->save();

        return $meta;
    }

    public function syncReviewPhotos(Review $review, bool $defaultPublished = false): void
    {
        foreach ($review->photos as $photo) {
            $this->ensureMeta($photo, $review, $defaultPublished);
        }
    }

    public function togglePublished(int $fileId): ReviewPhoto
    {
        $meta = $this->findMetaByFileId($fileId);
        $meta->is_published = !$meta->is_published;
        $meta->save();

        return $meta;
    }

    public function deletePhoto(int $fileId): void
    {
        $meta = $this->findMetaByFileId($fileId);
        $file = $meta->file;

        $meta->delete();

        if ($file) {
            $file->delete();
        }
    }

    public function findMetaByFileId(int $fileId): ReviewPhoto
    {
        $meta = ReviewPhoto::query()
            ->with(['file', 'review'])
            ->where('system_file_id', $fileId)
            ->first();

        if (!$meta) {
            throw new \ApplicationException('Фото не найдено');
        }

        return $meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildTileData(ReviewPhoto $meta): array
    {
        $meta->loadMissing(['file', 'review']);
        $review = $meta->review;
        $file = $meta->file;

        if (!$review || !$file) {
            throw new \ApplicationException('Связанные данные фото не найдены');
        }

        $reviewUrl = Backend::url('zen/reviews/reviews/update/' . (int) $review->id);

        return [
            'file_id' => (int) $file->id,
            'review_id' => (int) $review->id,
            'author_name' => (string) ($review->name ?: 'Без имени'),
            'ship_name' => (string) $review->ship_short_name,
            'review_date' => $review->created_at
                ? $review->created_at->format('d.m.Y H:i')
                : '—',
            'file_size' => $this->formatBytes((int) $file->file_size),
            'thumb_url' => (string) $file->getThumb(320, 240, ['mode' => 'crop']),
            'full_url' => (string) $file->getPath(),
            'review_url' => $reviewUrl,
            'is_published' => (bool) $meta->is_published,
            'file_name' => (string) $file->file_name,
        ];
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 2) . ' MB';
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateTiles(int $perPage = 24)
    {
        return ReviewPhoto::query()
            ->with(['file', 'review'])
            ->join('system_files', 'system_files.id', '=', 'zen_reviews_photos.system_file_id')
            ->where('system_files.attachment_type', Review::class)
            ->where('system_files.field', 'photos')
            ->select('zen_reviews_photos.*')
            ->orderBy('system_files.created_at', 'desc')
            ->paginate($perPage);
    }
}
