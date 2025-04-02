<?php

use Zen\Cabox\Classes\Cabox;

$cabox = new Cabox('dolphin.service');
$samopage_html = $cabox->get("hotel_id:".$model->eid);

//$samopage_html = preg_replace('/<table>.*<\/table>/', '', $samopage_html);

if($samopage_html):
?>
<style>
    #samopage {
        background: #0d70ff;
        padding: 50px;
        overflow-x: hidden;
    }
    #samopage > div {
        margin-top: -400px;
        margin-bottom: -1170px;
    }
</style>
<div id="samopage">
    <div>
        <?=$samopage_html?>
    </div>
</div>
<?php
endif;
