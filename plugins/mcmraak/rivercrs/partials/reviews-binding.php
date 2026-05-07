<?php
use Mcmraak\Rivercrs\Classes\ReviewsWidget;

$model = isset($model) ? $model : null;
$entityType = ReviewsWidget::detectEntityType($model);
$entityId = $model ? (int) $model->id : 0;
?>

<?php if (!$entityType || !$entityId): ?>
    <p class="help-block">Сначала сохраните запись, затем можно будет привязать отзывы.</p>
<?php else: ?>
    <div style="margin-bottom: 14px;">
        <a
            href="javascript:;"
            class="btn btn-primary"
            data-control="popup"
            data-handler="onReviewBindingLoadPopup"
            data-request-data="entity_type: '<?= e($entityType) ?>', entity_id: <?= $entityId ?>"
            data-size="large">
            Добавить
        </a>
    </div>

    <div id="reviews-bindings-list">
        <?= $this->makePartial('$/mcmraak/rivercrs/partials/reviews-binding-list.php', [
            'bindings' => ReviewsWidget::getBindings($entityType, $entityId),
            'entityType' => $entityType,
            'entityId' => $entityId,
        ]) ?>
    </div>
<?php endif; ?>
