<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Tour;
use Zen\Dolphin\Models\Page;

class DolphinPrintPage extends Core
{
    private Tour $tour;

    public function getData($scope)
    {
        $get = $this->input('get_params');
        $widgets_data = $this->input('result_items');
        $allowed_dates = $this->input('allowed_dates');
        if (!$get) {
            return null;
        }

        
        $get_data = json_encode($get);
        $get_data = json_decode($get_data);

        $tour_id = $get_data->id ?? null;
        if (!$tour_id) {
            return null;
        }
        $tour = $this->tour = Tour::find($tour_id);
        $page_id = $get_data->lp ?? null; // Страница с которой был переход, Пока не требуется
       
        $nights = $tour->duration - 1;

        return [
            'offersWidgetData' =>  $this->offersWidgetData($get_data), # данные поискового виджета по умолчанию
            'title' => $tour->name . " <span style='white-space:nowrap'>$tour->duration дн. / $nights ноч.</span>",
            'hotels' => $this->store('Hotels')->byAzimutTour($tour_id),
            'anonce' => $tour->anonce, # Анонс
            'waybill' => $tour->waybill_array, # Маршрут
            'important_info' => $tour->info, # Важная информация
            'conditions' => $tour->conditions_array, # Условия тура
            'tour_program' => $tour->tour_program, # Программа тура
            'allowed_dates' => $allowed_dates, # Возможные даты
            'widgets_data' =>  $widgets_data # Данные виджета 
        ];
    }

    public function offersWidgetData($get_data)
    {
        $get_data->tour_name = $this->tour->name;
        return $this->json($get_data, true);
    }
}
