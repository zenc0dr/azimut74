<?php

/** @var \Zen\Reviews\Models\Review $model */
echo \View::make('zen.reviews::photos_manage', ['model' => $model])->render();
