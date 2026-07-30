<?php namespace Zen\Reviews\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Zen\Reviews\Controllers\Traits\ManagesReviewPhotos;
use Zen\Reviews\Models\Review;

class Reviews extends Controller
{
    use ManagesReviewPhotos;

    public $implement = ['Backend\Behaviors\ListController', 'Backend\Behaviors\FormController'];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Reviews', 'reviews-main', 'reviews-reviews');
        $this->addCss('/plugins/zen/reviews/assets/css/review-photos-admin.css');
    }

    public function formAfterSave($model)
    {
        if ($model instanceof Review) {
            $this->photoService()->syncReviewPhotos($model, false);
        }
    }

    public function onTogglePublished()
    {
        $id = (int) post('id');
        $review = Review::find($id);
        if (!$review) {
            Flash::error('Отзыв не найден');

            return $this->listRefresh();
        }

        $review->is_published = !$review->is_published;
        $review->save();

        Flash::success($review->is_published ? 'Отзыв опубликован' : 'Отзыв снят с публикации');

        return $this->listRefresh();
    }
}
