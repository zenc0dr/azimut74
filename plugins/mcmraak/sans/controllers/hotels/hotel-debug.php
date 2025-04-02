<?php
$api_query = 'https://search-azimut:H343sd48j36@sapi.alean.ru:3443/services/json/?action=GetHotelDescription&id='.$model->id.'&lang=ru';
?>
<div>Запрос в AleanAPI: <a target="_blank" href="<?=$api_query?>"><?=$api_query?></a></div>
<iframe style="width:100%;height: 500px"
    src="/sans/api/v1/parser/hotel_profile/<?=$model->id?>?debug" frameborder="0"></iframe>