<?php if(!$value):?>
    <i style="color:green;font-size: 23px;position:absolute" class="icon-check-square-o"></i>
<?php else: ?>
    <i style="font-size:23px;position:absolute" class="icon-square-o"></i>
    <span style="margin-left:30px;color:red">
        <?=$value?>
    </span>
<?php endif; ?>