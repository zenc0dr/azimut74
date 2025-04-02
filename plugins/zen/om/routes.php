<?php

Route::post('/zen/om/recache/categoriescount', function () {
        return \Zen\Om\Models\Category::count();
});

Route::post('/zen/om/recache/itemscount', function () {
    return \Zen\Om\Models\Item::count();
});

Route::post('/zen/om/recache/updatecategories', function () {
        return \Zen\Om\Controllers\Categories::updateUrlCache(Input::get('page'),Input::get('steep'));
});

Route::post('/zen/om/recache/updateitems', function () {
    return \Zen\Om\Controllers\Items::updateUrlCache(Input::get('page'),Input::get('steep'));
});