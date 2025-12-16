<?php

namespace Zen\Quiz\Classes\Api;

class DebugQuiz
{
    # http://azimut74/zen/quiz/api/DebugQuiz:playground
    public function playground()
    {
        dd('ok');
    }

    # http://azimut74/zen/quiz/api/DebugQuiz:show
    public function show()
    {
        dd(232);
    }
}
