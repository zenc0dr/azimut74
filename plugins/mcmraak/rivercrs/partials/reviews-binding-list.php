<?php
$bindings = isset($bindings) ? $bindings : [];
$entityType = isset($entityType) ? $entityType : '';
$entityId = isset($entityId) ? (int) $entityId : 0;
?>

<?php if (!$bindings || !count($bindings)): ?>
    <p class="help-block">Пока нет привязанных отзывов.</p>
<?php else: ?>
    <table class="table data">
        <thead>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Теплоход</th>
            <th>Дата</th>
            <th style="width: 80px;">Действие</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($bindings as $binding): ?>
            <?php
            $review = $binding->review;
            if (!$review) {
                continue;
            }
            $data = $review->data ?: [];
            $form = (isset($data['form']) && is_array($data['form'])) ? $data['form'] : $data;
            ?>
            <tr>
                <td><?= (int) $review->id ?></td>
                <td><?= e($form['name'] ?? $review->name ?? 'Без имени') ?></td>
                <td><?= e($form['ship_name'] ?? '-') ?></td>
                <td><?= $review->created_at ? e($review->created_at->format('d.m.Y')) : '-' ?></td>
                <td>
                    <a
                        href="javascript:;"
                        class="btn btn-xs btn-danger"
                        style="width: 28px; height: 24px; padding: 0; display: inline-flex; align-items: center; justify-content: center;"
                        title="Отвязать"
                        data-request="onReviewBindingDetach"
                        data-request-confirm="Отвязать этот отзыв?"
                        data-request-data="entity_type: '<?= e($entityType) ?>', entity_id: <?= (int) $entityId ?>, binding_id: <?= (int) $binding->id ?>">
                        <i class="oc-icon-trash" style="margin: 0;"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
