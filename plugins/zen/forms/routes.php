<?php

Route::match(['get', 'post'], '/zen/forms/api/{path}:{method}', function (string $path, string $method) {
    return forms()->api($path, $method);
});
