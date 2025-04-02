<style>
    .log-data {
        font-size: 16px;
        font-weight: bold;
        color: #3d3e56;
        background-color: #fff;
        padding: 15px;
        border-radius: 5px;
    }
</style>

<?php
$data = $model->data;
$data = master()->fromJson($data);
$data = master()->toJson($data, true);
echo "<pre class='log-data'>$data</pre>";
?>
