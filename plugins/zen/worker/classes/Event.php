<?php namespace Zen\Worker\Classes;

use Zen\Worker\Models\Event as EventModel;

class Event
{
    public $event, $new = false;

    function __construct($code, $name)
    {
        $event = EventModel::where('code', $code)->where('success', 0)->orderBy('created_at', 'desc')->first();
        if(!$event) {
            $event = new EventModel;
            $this->new = true;
            $event->code = $code;
            $event->name = $name;
        }
        $this->event = $event;
    }

    function push($data)
    {
        $this->event->data = array_merge($this->event->data ?? [], $data);
    }

    function success($bool = null) {
        if($bool === null) {
            return filter_var($this->event->success, FILTER_VALIDATE_BOOLEAN);
        }
        $this->event->success = $bool;
    }

    function save()
    {
        $this->event->save();
    }

    function getData($key = null)
    {
        if(!$key) return $this->event->data;
        return @$this->event->data[$key];
    }

    static function hasOpenSession()
    {
        $event = EventModel::where('code', 'session')->where('success', 0)->first();
        if($event) return true;
        return false;
    }

}
