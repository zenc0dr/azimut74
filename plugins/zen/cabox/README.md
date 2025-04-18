#CacheBox
### Подготовка
Для начала нужно создать хранилища в админпанели указав некоторые параметры:
- Код хранилища - Например 'zen.items'
- Название хранилища - Любое
- Расположение хранилища - Например: ':storage/my_items'
- Время хранения в минутах - 0 = Вечный кэш
- Одна папка для всех файлов - Если включено, распределение по папкам не делается
- Сжатие данных

### Расположение хранилища
Чуть подробнее о расположении хранилища: слова :storage и :base
заменяются выводом функции storage_path() и base_path(). Но можно
указать любой адрес хранилища на сервере.

### Одна папка для всех файлов
На серверах имеется ограничение на количество файлов, так что лучше
чтобы эта опция была включена. Исключение составляют случаи когда файлов
кэша может быть очень много (больше 100000 файлов) и файловая система
сервера действительно реагирует на большое количество файлов и начинает
притормаживать (мной ниразу не замечено но ходит слух)

### Сжатие данных
Сжатие данных не сильно влияет на скорость загрузки кэша, на глаз точно
не заметно, но вот объём уменьшается в ДЕСЯТКИ раз.


## Как пользоваться

#### Создать объект для работы с хранилищем
```
$cache = new Cabox('zen.items');
```

#### Сохранить объект
```
$cache->put('item.key', $value);
```

#### Получить объект
```
$cache->get('item.key');
```
Если объект отсутствует или вышло указанное время, вернёт false.

#### Альтернативные способы получения
Иногда нужно оперировать временем отличным от указанного в настройках,
время указывается в минутах. null или false будет означать что время нужно взять
из настроек
```
$cache->get('item.key', 60);
```
---
Можно не шифровать имя файла в md5
```
$cache->get('item.key', false, false);
```
---
Можно получить полный массив данных, т.е. так-же и сервисные данные
```
$cache->get('item.key', false, false, true);
```
---
Получить по имени файла
```
$cache->getByFileName('7ab4fb90074c29804cc590ab147999fe')
```
#### Удалить объект
```
$cache->del('item.key');
```

#### Переобход всех объектов хранилища
Иногда нужно переобойти все объекты хранилища для того
чтобы собрать некоторые данные или даже изменить данные
внутри объектов хранилища.

```
$output = [];

$cache->handleItems(function ($item) use (&$output) {
    $time = $item['time'];
    $value = $item['value'];
    $key = $item['key'];

    $output[] = $key;
});

dd($output);
```
