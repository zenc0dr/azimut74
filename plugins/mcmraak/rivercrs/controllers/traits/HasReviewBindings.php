<?php namespace Mcmraak\Rivercrs\Controllers\Traits;

use Flash;
use Illuminate\Database\QueryException;
use Mcmraak\Rivercrs\Classes\ReviewsWidget;
use Zen\Reviews\Models\Binding;
use Zen\Reviews\Models\Review;

trait HasReviewBindings
{
    protected function getReviewBindingEntityType()
    {
        return defined('static::REVIEW_ENTITY_TYPE') ? static::REVIEW_ENTITY_TYPE : null;
    }

    protected function getReviewBindingEntityId()
    {
        return (int) post('entity_id');
    }

    protected function renderReviewBindingsList($entityType, $entityId)
    {
        return $this->makePartial('$/mcmraak/rivercrs/partials/reviews-binding-list.php', [
            'bindings' => ReviewsWidget::getBindings($entityType, $entityId),
            'entityType' => $entityType,
            'entityId' => $entityId,
        ]);
    }

    protected function renderReviewBindingsPopupList($entityType, $entityId)
    {
        $boundReviewIds = Binding::query()
            ->pluck('review_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();

        $query = Review::query()->orderBy('created_at', 'desc');
        if ($boundReviewIds) {
            $query->whereNotIn('id', $boundReviewIds);
        }

        return $this->makePartial('$/mcmraak/rivercrs/partials/reviews-binding-popup-list.php', [
            'reviews' => $query->get(),
            'entityType' => $entityType,
            'entityId' => $entityId,
        ]);
    }

    public function onReviewBindingLoadPopup()
    {
        $entityType = post('entity_type') ?: $this->getReviewBindingEntityType();
        $entityId = $this->getReviewBindingEntityId();

        if (!$entityType || !$entityId) {
            return '<div class="p-3">Сначала сохраните запись.</div>';
        }

        return $this->makePartial('$/mcmraak/rivercrs/partials/reviews-binding-popup.php', [
            'entityType' => $entityType,
            'entityId' => $entityId,
            'popupListHtml' => $this->renderReviewBindingsPopupList($entityType, $entityId),
        ]);
    }

    public function onReviewBindingAttach()
    {
        $entityType = post('entity_type') ?: $this->getReviewBindingEntityType();
        $entityId = $this->getReviewBindingEntityId();
        $reviewId = (int) post('review_id');

        if (!$entityType || !$entityId || !$reviewId) {
            Flash::error('Недостаточно данных для привязки отзыва.');
            return;
        }

        if (!Review::where('id', $reviewId)->exists()) {
            Flash::error('Отзыв не найден.');
            return;
        }

        try {
            Binding::create([
                'review_id' => $reviewId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);
        } catch (QueryException $ex) {
            Flash::error('Этот отзыв уже привязан к другой странице.');
        }

        return [
            '#reviews-bindings-list' => $this->renderReviewBindingsList($entityType, $entityId),
            '#reviews-bindings-popup-list' => $this->renderReviewBindingsPopupList($entityType, $entityId),
        ];
    }

    public function onReviewBindingDetach()
    {
        $entityType = post('entity_type') ?: $this->getReviewBindingEntityType();
        $entityId = $this->getReviewBindingEntityId();
        $bindingId = (int) post('binding_id');

        if (!$entityType || !$entityId || !$bindingId) {
            Flash::error('Недостаточно данных для удаления привязки.');
            return;
        }

        Binding::query()
            ->where('id', $bindingId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->delete();

        return [
            '#reviews-bindings-list' => $this->renderReviewBindingsList($entityType, $entityId),
            '#reviews-bindings-popup-list' => $this->renderReviewBindingsPopupList($entityType, $entityId),
        ];
    }
}
