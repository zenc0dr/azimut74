#Как использовать:
#### Подключить
use Zen\Sms\Classes\Sms;

#### Отправить sms
```
Sms::send($phone, $text, $profile_code);
```
* $phone - Телефон для отправки
* $text - Текст сообщения
* $profile_code - Код профиля