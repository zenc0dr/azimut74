if(!@$input['tour_name']) return;

$tour_name = null;
$days = null;

if(is_array($input['tour_name'])) {
    $tour_name = $input['tour_name']['text'];
    $days = $input['tour_name']['days'];
} else {
    $tour_name = $input['tour_name'];
}


$template['text'] = $tour_name;
$template['days'] = $days;