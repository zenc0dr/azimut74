<?php namespace Zen\Qm\Classes;

class Core
{
    public function get($var_name, $filter_name=null)
    {
        $value = Input::get($var_name);
        if(is_string($value)) {

            if($filter_name)
                switch ($filter_name) {
                    case 'only_digits':
                        $value = preg_replace('/\D/','', $value);
                        break;
                    case 'bool':
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'date':
                        $date = Carbon::parse($value);
                        $value = $date->format('Y-m-d');
                        break;
                }

            return trim($value);
        }
        return $value;
    }

    public function json($array)
    {
        echo json_encode ($array, JSON_UNESCAPED_UNICODE);
    }

    public function oneAlert($text, $type = 'success', $field = '', $additive=null)
    {
        $return = ['alerts' => [[
            'field' => $field,
            'type' => $type,
            'text' => $text
        ]]];

        if($additive) $return = $return + $additive;

        $this->json($return);
    }
}