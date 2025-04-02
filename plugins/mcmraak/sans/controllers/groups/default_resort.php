<?php

$resort = \Mcmraak\Sans\Models\Resort::where('id', $value)->first();
if($resort) echo $resort->name;