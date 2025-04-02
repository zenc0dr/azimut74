<?php

Route::group(['middleware' => 'web'], function () {

    Route::post('/blocks/gallery', function () {
        //echo Input::get('field') .' = '. Input::get('code') .' = '. Input::get('style');
        echo \Mcmraak\Blocks\Controllers\Galleries::getGallery(Input::all());
    });

    Route::match(['get', 'post'],'/blocks/injects', function () {
        \Mcmraak\Blocks\Controllers\Injects::getIjects();
    });

});
