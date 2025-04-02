<?php if($value): ?>
    <h4>Замечены отсутствующие номера кают:</h4>
    <?php

    $values = explode(',', $value);
    $echo = '';
    foreach ($values as $room) {
        $echo .= "<span>$room</span>";
    }
    echo "<notexistrooms>$echo</notexistrooms>"

    ?>
<?php endif; ?>