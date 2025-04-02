<?php
Route::group(['middleware' => 'web'], function () {
    # Api gateway
    Route::match(['get', 'post'], '/zen/dolphin/api/{class}:{method}', function ($class, $method) {
        if(!$class || !$method) die;
        $class = ucfirst($class);
        $action = "$class@$method";
        return App::call('Zen\Dolphin\Api\\'.$action);
    });
});


# FOR DEVELOPMENT
############################################################################################################################

# Для vue cli на фронте
Route::get('/dolphin/site.css', function(){
    return app('Zen\Dolphin\Api\Debug')->siteCSS();
});

# Для vue cli на бекенде
Route::get('/zen/dolphin/api/font/fontawesome-webfont.woff', function () {
    return response(file_get_contents(base_path('modules/system/assets/ui/font/fontawesome-webfont.woff')));
});

Route::get('/zen/dolphin/fonts/{font_dir}/{font_name}', function ($font_dir, $font_name) {
    return response(file_get_contents(base_path("themes/azimut-tur/assets/fonts/$font_dir/$font_name")));
});


Route::get('/zen/dolphin/api/images/loader-transparent.svg', function () {
    return response(file_get_contents(base_path('modules/system/assets/ui/images/loader-transparent.svg')))->header('Content-Type', 'image/svg+xml');
});
