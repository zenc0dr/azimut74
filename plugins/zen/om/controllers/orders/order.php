<?php
use RainLab\User\Models\User;
echo \View::make(
        'zen.om::order',
        [
            'order' => $model,
            'user' => User::find($model->user_id)
        ]
);
