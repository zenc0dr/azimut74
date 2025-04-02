# Zen.Lab
#### Набор рабочих классов

### Lab::validator
Пример:
```
$validator = Lab::validator();

$input_data = [
    'name|Имя|min:3|max:255|required' => $name,
    'phone|Телефон|min:11|max:11|required' => $phone,
    'email|email|email|min:3|max:300|required'
];

$validator->validate($input_data);

dd($validator->alerts());
```
Вывод ошибок выглядит так:
```
array:3 [
  0 => array:3 [
    "text" => "Поле "Имя" должно быть не короче 3 символов."
    "type" => "danger"
    "field" => "name"
  ]
  1 => array:3 [
    "text" => "Поле "Телефон" должно быть не короче 11 символов."
    "type" => "danger"
    "field" => "phone"
  ]
  2 => array:3 [
    "text" => "Поле "email" имеет ошибочный формат."
    "type" => "danger"
    "field" => "email"
  ]
]
```
