<?php

$form = \Mcmraak\Rivercrs\Classes\ReviewsWidget::extractForm($model);

echo \View::make('zen.reviews::review', ['form' => $form, 'model' => $model])->render();
