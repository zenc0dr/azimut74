<?php
Route::group(['middleware' => 'web'], function () {
    Route::match(['get', 'post', 'options'], '/master.api.{class}.{method}', function ($class, $method) {
        return app("Zen\Master\Api\\$class")->{$method}();
    });
});

