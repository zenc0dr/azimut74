<?php

Route::group(['middleware' => 'web'], function () {
   Route::match(['get', 'post'], '/zen/history/api/{class}:{method}', function ($class, $method) {
        if (!$class || !$method) {
            die;
        }
        $class = ucfirst($class);
        $action = "$class@$method";
        return App::call('Zen\History\Api\\'.$action);
    });
});
