<?php
Route::group(['middleware' => 'web'], function () {
    # Api gateway
    Route::match(['get', 'post'], '/zen/gt/api/{class}:{method}', function ($class, $method) {
        if (!$class || !$method) {
            die;
        }
        $class = ucfirst($class);
        $action = "$class@$method";
        return App::call('Zen\GroupTours\Api\\'.$action);
    });
});
