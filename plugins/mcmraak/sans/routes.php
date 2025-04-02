<?php

Route::group(['middleware' => 'web'], function () {

    Route::get('/sans/api/v1/groups/{root_id}', function ($root_id) {
        echo \Mcmraak\Sans\Controllers\Roots::getRootGroups($root_id);
    });

    Route::get('/sans/api/v1/parser/lists/{parser?}', function ($parser) {
    echo \Mcmraak\Sans\Controllers\Parser::parseLists($parser);
    });

    Route::get('/sans/api/v1/search/resorts_list', function () {
        # echo \Mcmraak\Sans\Controllers\Resorts::getResortsNested(); # deprecated
        echo \Mcmraak\Sans\Controllers\Resorts::getResortsNestedHtml();
    });

    Route::match(['get', 'post'], '/sans/api/v1/search/query', function () {
        echo Mcmraak\Sans\Classes\Search::searchResult(Input::all());
    });

    /* Parse hotel description */
    Route::get('/sans/api/v1/parser/hotels_list', function () {
        echo \Mcmraak\Sans\Controllers\Parser::hotelsList();
    });

    Route::get('/sans/api/v1/parser/hotel_profile/{hotel_id}', function ($hotel_id) {
        echo \Mcmraak\Sans\Controllers\Parser::hotelProfile($hotel_id);
    });

     Route::get('/sans/api/v1/parser/room_profile/{hotel_id}/{room_id}', function ($hotel_id, $room_id) {
        echo \Mcmraak\Sans\Controllers\Parser::hotelProfile($hotel_id, 1, $room_id);
    });

    Route::get('/sans/api/v1/parser/savetime/{prefix}', function ($prefix) {
        echo \Mcmraak\Sans\Controllers\Parser::saveParserTime($prefix);
    });

    # DEBUG
    Route::get('/sans/api/v1/debug/dump/{name}', function ($name) {
        \Mcmraak\Sans\Classes\Search::dumpArray($name);
    });
    Route::get('/sans/api/v2/debug/resorts', '\Mcmraak\Sans\Controllers\Resorts@getResortsNestedHtml');

    /* WRAPS */
    Route::post('/sans/api/v1/wraps/getdata', function () {
        echo \Mcmraak\Sans\Controllers\Wraps::getData();
    });
    Route::post('/sans/api/v1/wraps/select', function () {
        echo \Mcmraak\Sans\Controllers\Wraps::select();
    });
    Route::post('/sans/api/v1/wraps/add', function () {
        echo \Mcmraak\Sans\Controllers\Wraps::add();
    });

    /* Review */
    Route::match(['get', 'post'], '/sans/reviews/api/{action?}', function ($action = null){
        Mcmraak\Sans\Classes\Reviews::api($action);
    });

    /* Booking */
    Route::match(['get', 'post'], '/sans/api/v1/booking/{action?}', function ($action = null){
        Mcmraak\Sans\Classes\Booking::api($action);
    });

    Route::get('/sans/api/test', 'Mcmraak\Sans\Classes\Search@test');


});