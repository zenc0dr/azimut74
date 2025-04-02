<?php
Route::group(['middleware' => 'web'], function () {
    # Api gateway (only backend admins)
    Route::match(['get', 'post'], '/zen/cli/api/{action?}', function ($action = null) {
        if(!BackendAuth::check()) die('Access denied');
        return App::call('Zen\Cli\Api\\'.$action);
    });
});
