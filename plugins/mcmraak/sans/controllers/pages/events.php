<?php



$page_id = $record->id;

\DB::unprepared("SET sql_mode = ''");
$options = \DB::table('mcmraak_sans_wraps')
    ->where('page_id', $page_id)
    ->groupBy('name')
    ->orderBy('type_id')
    ->get()
    ->pluck('name')
    ->toArray();

echo '['.join(']<br>[', $options).']';