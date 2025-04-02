<?php namespace Mcmraak\Rivercrs\Classes;

use Validator;
use Input;
use Log;

class Api
{
    public $input;

    public function __construct()
    {
        $this->input = (object)Input::all();
        #Log::debug(print_r($this->input, 1));
    }

    ### Input fields ###
    public function input($var, $method = null)
    {
        if (!isset($this->input->{$var})) {
            return;
        }
        $var = $this->input->{$var};
        if ($method == 'trim') {
            return trim($var);
        }
        if ($method == 'num') {
            return preg_replace("/\D/", '', $var);
        }
        return $this->input->{$var};
    }

    ### Array to json ###
    public function json($array)
    {
        echo json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    ### Validation ###
    # Возвращает массив вида:
    #  'alerts' => [
    #    0 => [
    #      'text' => 'Поле имя обязательно для заполнения.',
    #      'type' => 'danger',
    #      'field' => 'name',
    #    ]
    #  ],
    public function validate($data)
    {
        $return = [
            'alerts' => [],
        ];
        $make_a = [];
        $make_b = [];
        foreach ($data as $key => $val) {
            $names = explode('|', $key);
            $make_a[$names[1]] = $val[0];
            $make_b[$names[1]] = $val[1];
        }

        $validator = Validator::make($make_a, $make_b);

        if ($validator->fails()) {
            $messages = $validator->messages();
            foreach ($data as $key => $val) {
                $names = explode('|', $key);
                if ($messages->has($names[1])) {
                    $return['alerts'][] = [
                        'text' => $messages->first($names[1]),
                        'type' => 'danger',
                        'field' => $names[0]
                    ];
                }
            }
        }
        return $return;
    }
}
