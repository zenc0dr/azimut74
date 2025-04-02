<?php namespace Mcmraak\Rivercrs\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Carbon\Carbon;
use Mcmraak\Rivercrs\Classes\CacheSettings as Settings;
use Input;
use Queue;
use DB;

class Checkins extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = ['mcmraak.rivercrs'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-checkins');
        $this->addJs("/plugins/mcmraak/rivercrs/assets/node_modules/vue/dist/vue.min.js");
        $this->addCss('/plugins/mcmraak/rivercrs/assets/css/rivercrs.checkins.css');
    }

    public static function graphicModal($checkin_id)
    {
        $checkin = \Mcmraak\Rivercrs\Models\Checkins::find($checkin_id);

        return \View::make(
            'mcmraak.rivercrs::graphic_modal',
            [
                'checkin' => $checkin,
                'to_print' => Input::get('to_print')
            ]
        );
    }

    public static function bookingModal($checkin_id)
    {
        $checkin = \Mcmraak\Rivercrs\Models\Checkins::find($checkin_id);
        $weekdays = ['вс','пн','вт','ср','чт','пт','сб'];
        $anchor_assistant = Settings::get('anchor_assistant');

        # Всегда открывать модальное окно с внешними источниками
        return \View::make(
            'mcmraak.rivercrs::booking_exist_modal',
            [
                'checkin' => $checkin,
                'weekdays' => $weekdays,
                'anchor_assistant' => $anchor_assistant,
                'modal_info' => Settings::get('booking_modal_info'),
                'statuses' => Settings::get('cabin_statuses'),
                'offer' => \Mcmraak\Blocks\Models\Offer::find(1)
            ]
        );
    }

    public function onDeleteOld()
    {
        $date = \Input::get('date');
        $date = Carbon::parse($date);
        $date = $date->format('Y-m-d').' 00:00:00';

        $records = Checkin::where('date', '<', $date)->get();

        foreach ($records as $record) {
            Queue::push('\Mcmraak\Rivercrs\Controllers\Checkins@deleteOldCheckinJob', [
                'checkin_id' => $record->id
            ], 'RiverCrs.removeChekin');
        }

        \Flash::success('На удаление поставлено заездов: '.$records->count());
    }

    public function deleteOldCheckinJob($job, $data){
        $checkin_id = $data['checkin_id'];
        $checkin = Checkin::find($checkin_id);
        if($checkin) $checkin->delete();
        $job->delete();
    }

    public function recacheChekin($checkin_id)
    {
        return; // method deprecated
        app('\Mcmraak\Rivercrs\Classes\Search')->delCache($checkin_id);
        $job_exist = DB::table('jobs')
            ->where('queue', 'RiverCrs.recacheChekin')
            ->where('payload', 'like', '%"checkin_id":'.$checkin_id.'%')
            ->count();

        if(!$job_exist) {
            Queue::push('\Mcmraak\Rivercrs\Classes\Search@renderCheckinJob', [
                'checkin_id' => $checkin_id
            ], 'RiverCrs.recacheChekin');
        }
    }

}
