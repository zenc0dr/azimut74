<?php $popupListHtml = isset($popupListHtml) ? $popupListHtml : ''; ?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="popup">&times;</button>
    <h4 class="modal-title">Добавить отзыв</h4>
</div>
<div class="modal-body">
    <div id="reviews-bindings-popup-list">
        <?= $popupListHtml ?>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="popup">Закрыть</button>
</div>
