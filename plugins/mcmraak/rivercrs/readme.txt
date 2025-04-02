# Формирование графика движения Волга-Волга

Пример: http://azimut.s/russia-river-cruises/motorship/23/44090

График формируется:

Роут: http://azimut.s/rivercrs/api/v1/checkin/modalgraphic/44090
Класс: plugins/mcmraak/rivercrs/controllers/Checkins.php > graphicModal()
Представление: plugins/mcmraak/rivercrs/views/graphic_modal.htm

/var/www/azimut.s/plugins/mcmraak/rivercrs/models/Checkins.php > volgaSchelude()
\Mcmraak\Rivercrs\Controllers\VolgaSettings::getVolgaExcursion($this);

Отладка: /rivercrs/volga_debug?checkin=44090
