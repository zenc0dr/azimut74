<?php


Route::group(['middleware' => 'web'], function () {

    # DEBUG MAIN route # НЕ ТРОГАТЬ!
    Route::get('/rivercrs/debug/{method}', function($method){
        return App::call('Mcmraak\Rivercrs\Classes\\'.$method);
    });

    /* BACKEND */
    Route::post('/rivercrs/api/v1/pricing/get', function () {
        $checkin_id = Input::get('checkin_id');
        $motorship_id = Input::get('motorship_id');
        return \Mcmraak\Rivercrs\Models\Pricing::Pivot($checkin_id, $motorship_id);
    });

    Route::get('/rivercrs/api/v1/pricing/motorship/{motorship_id}', function ($motorship_id) {
        return \Mcmraak\Rivercrs\Models\Pricing::Motorship($motorship_id);
    });

    Route::get('/rivercrs/api/v1/pricing/towns/{motorship_id}', function ($motorship_id) {
        echo \Mcmraak\Rivercrs\Models\Pricing::getTownsOfMotorsip($motorship_id);
    });

    Route::get('/rivercrs/api/v1/pricing/price/{motorship_id}/{town}', function ($motorship_id, $town_id) {
        return \Mcmraak\Rivercrs\Models\Pricing::PriceXLS($motorship_id, $town_id);
    });

    Route::match(['get', 'post'], '/rivercrs/api/v1/pricing/price/upload', function () {
        return \Mcmraak\Rivercrs\Models\Pricing::UploadXLS(Input::file());
    });
    /* END OF BACKEND */


    # Get start data for search widget v1
    Route::get('/rivercrs/api/v1/selector/filter', function () {
        return \Mcmraak\Rivercrs\Controllers\Selector::filter();
    });

    # Get start data for search widget v2
    Route::match(['get', 'post'], '/rivercrs/api/v2/search/{action}', function ($action) {
        return App::call("Mcmraak\Rivercrs\Classes\Search@$action");
    });

//    Route::get('/rivercrs/api/v1/selector/filter/debug', function () {
//        return \Mcmraak\Rivercrs\Controllers\Selector::softRelation();
//    });

    # Result for widget query
    Route::match(['get', 'post'], '/rivercrs/api/v1/selector/htmlresult', function () {
        return \Mcmraak\Rivercrs\Controllers\Selector::idsQueryString(Input::all());
    });

    # Cabin modal window content
    Route::get('/rivercrs/api/v1/cabin/modal/{cabin_id}', function ($cabin_id) {
        return \Mcmraak\Rivercrs\Controllers\Cabins::cabinModal($cabin_id);
    });

    Route::get('/rivercrs/api/v1/checkin/modalgraphic/{checkin_id}', function ($checkin_id) {
        return \Mcmraak\Rivercrs\Controllers\Checkins::graphicModal($checkin_id);
    });

    Route::get('/rivercrs/api/v1/checkin/modalbooking/{checkin_id}', function ($checkin_id) {
        return \Mcmraak\Rivercrs\Controllers\Checkins::bookingModal($checkin_id);
    });

//    Route::post('/rivercrs/api/v1/booking/send', function () {
//        return \Mcmraak\Rivercrs\Controllers\Booking::sendBooking(Input::get('data'));
//    });
    Route::post('/rivercrs/api/v2/booking/send', function () {
        return App::call('Mcmraak\Rivercrs\Controllers\Booking@sendBooking');
    });


    Route::post('/rivercrs/api/v1/review/send', function () {
        return \Mcmraak\Rivercrs\Controllers\Reviews::sendReview(Input::all());
    });

    # DEBUG
    #Route::get('/rivercrs/debug_filter', 'Mcmraak\Rivercrs\Controllers\Selector@idsQueryString');
    # DEBUG UPLOAD
    Route::get('/rivercrs/volga_debug', 'Mcmraak\Rivercrs\Controllers\VolgaSettings@getVolgaExcursion');

    # DEBUG onRemoveNotActual
    Route::get('/rivercrs/ids_memory', 'Mcmraak\Rivercrs\Classes\Idmemory@idsCount');

    # DELETE EDS
    Route::get('/rivercrs/api/v1/remove_eds/{eds_code}', function ($eds_code) {
        if(!BackendAuth::check()) return;
        return \Mcmraak\Rivercrs\Classes\Getter::removeEds($eds_code);
    });

    #DEBUG Germes schelude
    Route::get('/rivercrs/debug_germes_schelude', 'Mcmraak\Rivercrs\Classes\Exist\GermesSchelude@render');



    Route::get('/rivercrs/api/v2/cache/{type}/{method}/{id?}', function($type, $method, $id=null){
        return App::call('Mcmraak\Rivercrs\Classes\Parser@cacheWarmUp', [
            'method' => $method,
            'type' => $type,
            'vars' => ($id)?['id'=>$id]:false,
        ]);
    });

    Route::match(['get', 'post'], '/rivercrs/api/v2/parser/{method}', function ($method) {
        return App::call("Mcmraak\Rivercrs\Classes\Getter@$method", ['method' => $method]);
    });

    Route::get('/rivercrs/api/v2/exist/{checkin_id}', function ($checkin_id){
        App::call('Mcmraak\Rivercrs\Classes\Exist@get', [
            'checkin_id' => $checkin_id,
            'type' => 'json'
        ]);
    });

    Route::post('/rivercrs/api/v2/cabin/open', 'Mcmraak\Rivercrs\Classes\Exist@getCabin');

    ### AZIMUT-TUR-PRO ###
    Route::match(['get', 'post'], '/rivercrs/api/{method}', function ($method) {
        if (!$method) {
            die;
        }
        return App::call("Mcmraak\Rivercrs\Classes\RivercrsApi@$method");
    });

    ### PRO-KRUIZ ###
    Route::match(['get', 'post'], '/prok/api/{method}', function($method) {
        if(!$method) die;
        return App::call("Mcmraak\Rivercrs\Classes\Prok@$method");
    });



});
