<?php

Route::match(['get', 'post'], '/zen/quiz/api/{path}:{method}', function (string $path, string $method) {
    return quiz()->api($path, $method);
});
