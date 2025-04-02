<?php

if($model->gallery)

echo View::make('zen.dolphin::hotel_images', ['hotel' => $model]);
