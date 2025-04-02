<?php

Route::match(['get', 'post'], '/fetcher/api/{path}:{method}', function (string $path, string $method) {
    return fetcher()->api($path, $method);
});
