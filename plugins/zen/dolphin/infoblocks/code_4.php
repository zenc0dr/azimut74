if(!@$input['warning_info']) return;

$warning_info = trim($input['warning_info']);

$warning_info = preg_replace('/\*\*([^*]+)\*\*/', '<span style="font-style:italic;">$1</span>', $warning_info);
$warning_info = preg_replace('/\*([^*]+)\*/', '<span style="font-weight:bold">$1</span>', $warning_info);

$template['text'] = $warning_info;