<?php

Route::group(['middleware' => 'web'], function () {
    # Api gateway
    Route::match(['get', 'post'], '/zen/putpics/api/{class}:{method}', function ($class, $method) {
        if (!$class || !$method) {
            die;
        }
        $class = ucfirst($class);
        $action = "$class@$method";
        return App::call('Zen\Putpics\Api\\'.$action);
    });
});
