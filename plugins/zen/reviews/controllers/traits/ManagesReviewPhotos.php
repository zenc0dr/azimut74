<?php namespace Zen\Reviews\Controllers\Traits;

use Flash;
use Zen\Reviews\Classes\ReviewPhotoService;
use Zen\Reviews\Controllers\Photos;
use Zen\Reviews\Models\Review;

trait ManagesReviewPhotos
{
    protected function photoService(): ReviewPhotoService
    {
        return new ReviewPhotoService();
    }

    public function onTogglePhotoPublished()
    {
        $fileId = (int) post('file_id');
        $meta = $this->photoService()->togglePublished($fileId);

        Flash::success($meta->is_published ? 'Фото опубликовано' : 'Фото снято с публикации');

        return $this->refreshPhotoViews((int) $meta->review_id);
    }

    public function onDeletePhoto()
    {
        $fileId = (int) post('file_id');
        $meta = $this->photoService()->findMetaByFileId($fileId);
        $reviewId = (int) $meta->review_id;

        $this->photoService()->deletePhoto($fileId);

        Flash::success('Фото удалено');

        return $this->refreshPhotoViews($reviewId);
    }

    /**
     * @return array<string, string>
     */
    protected function refreshPhotoViews(int $reviewId = 0): array
    {
        $response = [];

        if ($this instanceof Photos) {
            $this->vars['photos'] = $this->photoService()->paginateTiles(24);
            $response['#photos-grid'] = $this->makePartial('photo_grid');
            $response['#photos-pagination'] = $this->makePartial('pagination');
        }

        if ($reviewId > 0) {
            $review = Review::with(['photos', 'reviewPhotos'])->find($reviewId);
            if ($review) {
                $response['#review-photos-manage'] = $this->makePartial(
                    '$/zen/reviews/views/photos_manage.htm',
                    ['model' => $review]
                );
            }
        }

        return $response;
    }
}
