<?php
$reviews = isset($reviews) ? $reviews : [];
$entityType = isset($entityType) ? $entityType : '';
$entityId = isset($entityId) ? (int) $entityId : 0;
?>

<?php if (!$reviews || !count($reviews)): ?>
    <p class="help-block">Свободных отзывов нет.</p>
<?php else: ?>
    <table class="table data">
        <thead>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Теплоход</th>
            <th>Дата</th>
            <th style="width: 120px;">Действие</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reviews as $review): ?>
            <?php
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
                        class="btn btn-xs btn-primary"
                        data-request="onReviewBindingAttach"
                        data-request-data="entity_type: '<?= e($entityType) ?>', entity_id: <?= (int) $entityId ?>, review_id: <?= (int) $review->id ?>">
                        Добавить
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
