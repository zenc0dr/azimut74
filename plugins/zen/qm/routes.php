<?php

Route::group(['middleware' => 'web'], function () {
    Route::match(['get', 'post'], '/zen/qm/api/{action?}', function ($action = null){
        return App::call('Zen\Qm\Api\\'.$action, ['action' => $action]);
    });
});