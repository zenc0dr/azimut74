<?php

if ($value) {
    $backend_user = \Backend\Models\User::find($value);
    echo "$backend_user->email";
}
