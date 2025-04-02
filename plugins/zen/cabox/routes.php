<?php
Route::group(['middleware' => 'web'], function () {
    # Api gateway
    Route::match(['get', 'post'], '/zen/cabox/api/{action?}', function ($action = null) {
        return App::call('Zen\Cabox\Api\\'.$action);
    });
});

Route::get('/zen/cabox/image/{storage_id}/{key}', function ($storage_id, $key) {
    return \Zen\Cabox\Classes\Cabox::getImage($storage_id, $key);
});
