if(@$input['dolphin_tour']['Parts']['Fields'][0]) {
    $template['tour_program'] = [];
    $days = $input['dolphin_tour']['Parts']['Fields'][0];
    
    $dow = [
        'Воскресенье',
        'Понедельник',
        'Вторник',
        'Среда',
        'Четверг',
        'Пятница',
        'Суббота',
        ];
    
    foreach($days as $day) {
        $day_num = intval($day['DayOfWeek']);
        
        $desc = @$day['Info']['Detail'];
        
        if($desc) {
            $desc = str_replace("\r\n", '<br>', $desc);
            //$desc = preg_replace("/\*\*([^*]+)\*\*/", '<strong>$1</strong>', $desc);
            
            $desc = preg_replace('/\*\*([^*]+)\*\*/', '<span style="font-style:italic;">$1</span>', $desc);
            $desc = preg_replace('/\*([^*]+)\*/', '<span style="font-weight:bold">$1</span>', $desc);
            
            
            $template['tour_program'][] = [
                'day' => $dow[$day_num],
                'desc' => $desc
            ];
        }
    }
}