<?php

Route::match(['get', 'post'], '/zen/reviews/api/{path}:{method}', function (string $path, string $method) {
    return reviews()->api($path, $method);
});
