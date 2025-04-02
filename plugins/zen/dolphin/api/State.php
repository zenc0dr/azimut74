<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Parser;

class State extends Parser
{

    # http://azimut.dc/zen/dolphin/api/state:get
    function get()
    {
        $this->isAdmin();

        $state = $this->getState();

        if(!$state) return;

        if($state['progress']) {
            $progress = [];
            foreach ($state['progress'] as $handler => $data) {
                $progress[] = [
                    'title' => $data['name'],
                    'time' => $data['time'],
                    'text' => $data['value']
                ];
            }
            $state['progress'] = $progress;
        }

        # Запущен ли процесс
        if($this->processIsLaunched('dolphin:go')) {
            $state['status']['process'] = true;
        } else {
            $state['status']['process'] = false;
        }

        $this->json($state);
    }
}
