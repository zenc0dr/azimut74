<?php namespace Mcmraak\Rivercrs\Classes;

use Carbon\Carbon;

class ScheduleBuilder
{
    private $schedule_table = [];
    private $dow_arr = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
    private $full_dow_arr = ['Воскресенье','Понедельник','Вторник','Среда','Четверг','Пятница','Суббота'];
    public
        $day,
        $date_arrive, # Дата прибытия
        $date_depart, # Дата отправления
        $dof_arrive,  # День недели отправления
        $dof_depart,  # День недели прибытия
        $time_arrive, # Время прибытия
        $time_depart, # Время отправления
        $time_diff,   # Время стоянки
        $town,        # Город
        $desc;        # Описание

    public $action = true;

    function getSchedule()
    {
        return $this->schedule_table;
    }

    function addDay()
    {
        $this->day = intval($this->day);
        if($this->date_arrive) $this->date_arrive = Carbon::parse($this->date_arrive);
        if($this->date_depart) $this->date_depart = Carbon::parse($this->date_depart);

        $this->getDofArrive();
        $this->getDofDepart();
        $this->timeDiff();

        $this->schedule_table[] = [
            'day' => $this->day,
            'date' =>        $this->date_arrive ?? $this->date_depart,
            'dof' =>         $this->dof_arrive ?? $this->dof_depart,
            'action' =>      $this->getAction(),
            'date_arrive' => ($this->date_arrive) ? $this->date_arrive->format('d.m.Y') : null,
            'date_depart' => ($this->date_depart) ? $this->date_depart->format('d.m.Y') : null,
            'time_arrive' => $this->time_arrive,
            'time_depart' => $this->time_depart,
            'dof_arrive' =>  $this->dof_arrive,
            'dof_depart' =>  $this->dof_depart,
            'time_diff' =>   $this->time_diff,
            'town' =>        $this->town,
            'desc' =>        $this->desc,
        ];

        $this->day = null;
        $this->date_arrive = null;
        $this->date_depart = null;
        $this->time_arrive = null;
        $this->time_depart = null;
        $this->dof_arrive = null;
        $this->dof_depart = null;
        $this->time_diff = null;
        $this->town = null;
        $this->desc = null;
    }

    private function getDofArrive()
    {
        if(!$this->date_arrive) return;
        if($this->dof_arrive) return;
        $this->dof_arrive = $this->getDow($this->date_arrive);
    }

    private function getDofDepart()
    {
        if(!$this->date_depart) return;
        if($this->dof_depart) return;
        $this->dof_depart = $this->getDow($this->date_depart);
    }

    private function getDow($date)
    {
        return [
            's' => $this->dow_arr[$date->dayOfWeek],
            'f' => $this->full_dow_arr[$date->dayOfWeek]
        ];
    }

    private function timeDiff()
    {
        if($this->time_diff) return;
        if(!$this->time_arrive || !$this->time_depart) return;
        $time1 = strtotime($this->time_arrive.':00');
        $time2 = strtotime($this->time_depart.':00');
        $difference = round(abs($time2 - $time1) / 3600,2);

        $difference = number_format($difference,2,':','');
        $this->time_diff = $difference;
    }

    private function getAction()
    {
        if(!$this->action) return;
        if($this->time_arrive && !$this->time_depart) return 'Прибытие';
        if(!$this->time_arrive && $this->time_depart) return 'Отправление';
        if($this->time_arrive && $this->time_depart) return 'Стоянка';
    }
}
