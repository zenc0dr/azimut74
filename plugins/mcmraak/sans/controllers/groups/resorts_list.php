<?php

foreach ($model->resorts as $resort)
{
    echo "<div class='resort'>[{$resort->id}] {$resort->name}</div>";
}