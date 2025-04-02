<?php

echo View::make('zen.dolphin::partials.hotels.geo', ['geo_data' => $model->geo_data])->render();
