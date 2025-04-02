<?php



echo \View::make('zen.reviews::review', ['form' => $value, 'model' => $model])->render();
