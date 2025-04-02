<?php

Route::group(['middleware' => 'web'], function () {
    # Api gateway
    Route::match(['get', 'post'], '/zen/actions/{id}', function ($id) {
       \Zen\Actions\Classes\Core::run($id);
    });
});
