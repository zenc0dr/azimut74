<?php

use Srw\Catalog\Controllers\Sitemap;

//Route::get('sitemap.xml', function () {
//    return Response::make(Sitemap::generate())->header("Content-Type", "application/xml");
//});
# Данный функционал вынесен в отдельный плагин
//Route::get('robots.txt', function () {
//    return Response::make(Sitemap::robots())->header("Content-Type", "text/plain");
//});
Route::post('/srw/catalog/update', function () {
    return 'lolo';
});
