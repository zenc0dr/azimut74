<?php



$href = app('Zen\Robots\Controllers\Generate')->getDomain().'/russia-river-cruises/motorship/'.$model->motorship_id.'/'.$model->id;
echo "Открыть внешнюю ссылку: <a target='_blank' href='$href'>$href</a>";