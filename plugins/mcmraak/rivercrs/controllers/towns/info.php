<h6>Связанные города:</h6>
<ul>
    <?php
    foreach ($model->soft_items as $item){
        echo "<li>{$item->name}</li>";
    }
    ?>
</ul>