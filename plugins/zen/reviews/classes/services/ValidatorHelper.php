<?php namespace Zen\Reviews\Classes\Services;

use Validator;

class ValidatorHelper
{
    private array $alertsArr = [];

    public function validate($data)
    {
        $make_a = [];
        $make_b = [];

        foreach ($data as $key => $val) {
            $names = $opts = explode('|', $key);
            $opts = join('|', array_splice($opts, 2));

            $make_a[$names[1]] = $val;
            $make_b[$names[1]] = $opts;
        }

        $validator = Validator::make($make_a, $make_b);

        if ($validator->fails()) {
            $messages = $validator->messages();
            foreach ($data as $key => $val) {
                $names = explode('|', $key);
                if ($messages->has($names[1])) {
                    $field_name = str_replace('_', ' ', $names[1]);
                    $text = $messages->first($names[1]);
                    $text = str_replace(mb_strtolower($field_name), '"' . $field_name . '"', $text);
                    $this->alertsArr[] = [
                        'text' => $text,
                        'type' => 'danger',
                        'field' => $names[0]
                    ];
                }
            }
        }
    }

    # Добавить к массиву уведомлений ещё одно
    public function addAlert($text, $type = 'success', $field = '')
    {
        $this->alertsArr[] = [
            'text' => $text,
            'type' => $type,
            'field' => $field
        ];
    }

    public function alerts()
    {
        return $this->alertsArr;
    }
}
