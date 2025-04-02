<?php namespace Zen\Dolphin\Classes;

# Это главный и центральный класс, его наследуют все, а кто не наследует
# тот нужен только как переходник который наследут класс который наследует этот класс
# Все use находятся тут, все хелперы тоже тут
# Сам класс core не должен содержать конструктор

use ToughDeveloper\ImageResizer\Classes\Image as Resizer;
use Zen\Grinder\Classes\Grinder;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Zen\Dolphin\Models\Log;
use Zen\Cabox\Classes\Cabox;
use Zen\Cli\Classes\Cli;
use Zen\Dolphin\Models\Settings;
use BackendAuth;
use Session;
use Input;
use Yaml;
use View;
use DB;
use Exception;

class Core
{
    public $cli;

    private $start_time;

    public function profStart()
    {
        $this->start_time = microtime(true);
    }

    public function profFix($var = null)
    {
        $time = microtime(true) - $this->start_time;
        printf("Фиксация %.4F сек.\n", $time);
        if ($var) {
            dd($var);
        }
    }

    # Проверка, является ли пользователь администратором
    public function isAdmin($return = false)
    {
        if (!BackendAuth::check()) {
            if ($return) {
                return false;
            }
            die('Access denied');
        }
        return true;
    }

    public function input($key, $options = null)
    {
        $value = Input::get($key);

        if (is_string($value)) {
            $value = trim($value);
        }

        if (is_string($options)) {
            $options = [$options];
        }

        if ($options) {
            foreach ($options as $option) {
                if ($option == 'only_digits') {
                    $value = preg_replace('/\D/', '', $value);
                }
                if ($option == 'bool') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if ($option == 'int') {
                    $value = intval($value);
                }
            }
        }

        return $value;
    }

    # Преобразовать массив в json-строку
    public function json($array, $return = false)
    {
        $json_string = json_encode($array, JSON_UNESCAPED_UNICODE);
        if ($return) {
            return $json_string;
        }
        echo $json_string;
    }

    # Добавить событие в журнал (type, source, text, dump)
    public function log($opts)
    {
        Log::add($opts);
    }

    public function model($model_name)
    {
        return app("\Zen\Dolphin\Models\\$model_name");
    }

    # Получить доступ к массиву настроек
    public function settings($key)
    {
        return Settings::get($key);
    }

    # Запрос к внешнему источнику (стандартный для дельфина)
    public function http($url, $post = null, $timeout = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($post) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            $post = json_encode($post, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . mb_strlen($post)
            ]);
        }

        $body = curl_exec($ch);
        $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);

        return (object)[
            'body' => $body,
            'code' => $code
        ];
    }

    # Преобразовать массив в html для изучения
    public function htmlDump($array, $items_limit = 300000)
    {
        $dumper = new HtmlDumper;
        $cloner = new VarCloner();
        $cloner->setMaxItems($items_limit);
        return $dumper->dump($cloner->cloneVar($array), true);
    }

    # Более насыщеный дамп (вместо dd())
    public function ddd($value)
    {
        $dump = $this->htmlDump($value);
        echo $dump;
        die;
    }

    # Функция подгружает объект cli
    public function enableCli()
    {
        if (!$this->cli) {
            $this->cli = new Cli;
        }
    }

    ##### Функции Cli
    # Отправить команду artisan в фон для выполнения
    public function artisanExec($artisan_command)
    {
        $this->enableCli();
        $this->cli->nohup = true;
        $command = $this->cli->artisanExec($artisan_command);
        $this->log([
            'type' => 'info',
            'source' => 'Core@artisanExec',
            'text' => $command
        ]);
    }

    # Проверить запущен ли процесс
    public function processIsLaunched($entry)
    {
        $this->enableCli();
        return $this->cli->processIsLaunched($entry);
    }

    # Завершить процесс
    public function killProcess($entry)
    {
        $this->enableCli();
        $this->cli->killProcess($entry);
    }
    ###############

    # Создать объект Cabox-кэша
    public function cache($storage_code)
    {
        return new Cabox($storage_code);
    }

    # Очистить элемент кэша
    public function clearCacheItem($storage_code, $cache_key)
    {
        $cache = $this->cache($storage_code);
        $cache->del($cache_key);
    }

    public function stream($token, $storage)
    {
        return new Stream($token, $storage);
    }

    # Создать кэш сниппетов для экземпляра одной из трёх моделй гео-объектов
    public function createSnippets($model)
    {
        $images = $model->snippets()->get();

        if (!$images) {
            return;
        }

        $resized = [];
        foreach ($images as $image) {
            $resized[] = $this->resize($image->path, ['width' => 500]);
        }

        DB::table($model->table)
            ->where('id', $model->id)
            ->update([
                'thumbs' => json_encode($resized, JSON_UNESCAPED_UNICODE)
            ]);
    }

    # Получить экземпляр Store по имени класса
    public function store($store_name)
    {
        return app("Zen\Dolphin\Store\\$store_name");
    }

    # Формат даты в mysql
    public function dateToMysql($date)
    {
        if (!$date) {
            return null;
        }
        $date = explode('.', $date);
        return $date[2] . '-' . $date[1] . '-' . $date[0];
    }

    # Формат даты из mysql
    public function dateFromMysql($date)
    {
        if (!$date) {
            return null;
        }
        $date = explode('-', $date);
        return $date[2] . '.' . $date[1] . '.' . $date[0];
    }

    # Обёртка для view
    public function view($code, $data = null)
    {
        return View::make('zen.dolphin::' . $code, $data)->render();
    }

    # Рендер кода для бэкенд-модалки
    public function modal($html, $title, $width = 80)
    {
        return $this->view('backend.modal', [
            'html' => $html,
            'title' => $title,
            'width' => $width
        ]);
    }

    # Получить данные для ZenSelect.vue
    public function vueSelect($essence, $value = 0, $options = false)
    {
        if ($options === false) {
            $options = app('\Zen\Dolphin\Models\\' . ucfirst($essence))
                ->select('id', 'name')
                ->get()
                ->toArray();
        }

        return [
            'value' => $value,
            'options' => $options
        ];
    }

    # Преобразует geo-объекты из БД в массив для запроса #TODO: Можно кешировать
    public function geoObjectsToAreas($geo_objects)
    {
        $model_names = [
            0 => 'Country',
            1 => 'Region',
            2 => 'City',
        ];

        $return = [];
        foreach ($geo_objects as $geo_object) {
            $S = explode(':', $geo_object);
            if (preg_match('/\D+/', $S[1])) {
                continue;
            }
            $model_name = $model_names[$S[0]];
            $id = $S[1];

            $eid = app("Zen\Dolphin\Models\\$model_name")->find($id)->eid;

            $return[] = $eid ?? 'loc_' . $id;
        }

        return $return;
    }

    # Возвращает путь до файла преобразуя его из disk_name (имени файла в хранилище)
    public function getDiskNamePath($disk_name)
    {
        return '/storage/app/uploads/public/'
            . implode('/', array_slice(str_split($disk_name, 3), 0, 3))
            . '/' . $disk_name;
    }

    public function sessionPut($key, $value)
    {
        Session::put($key, $value);
    }

    public function sessionGet($key)
    {
        return Session::get($key);
    }

    public function addException(Exception $ex, $source = 'system', $text = null, $ex_info = null)
    {
        $line = $ex->getLine();
        $message = $ex->getMessage();
        $trace = $ex->getTrace();
        $file = $ex->getFile();
        $error = "Ошибка: $message в файле $file:$line";
        $text = $text ?? $error;
        $this->log([
            'type' => 'Exception',
            'source' => $source,
            'text' => $text,
            'dump' => [
                'error' => $error,
                'trace' => $trace,
                'ex_info' => $ex_info
            ]
        ]);
    }

    public function dropError($text)
    {
        $this->json(['error' => $text]);
        die;
    }

    # Функция ресайза изображений
    /*
    public function resize($image_path, $opts = [])
    {
        $default = [
            'width' => false,
            'height' => false,
            'mode' => 'auto',
            'offset' => [0, 0],
            'extension' => 'auto',
            'quality' => 95,
            'sharpen' => 0,
            'compress' => true,
        ];

        $opts = array_merge($default, $opts);

        $imageObj = new Resizer($image_path);
        $imageObj->resize($opts['width'], $opts['height'], $opts);
        return $imageObj->__toString();
    }
    */
    public function resize($image_path, $opts = [])
    {

        $thumb = Grinder::open($image_path);

        if (isset($opts['width'])) {
            $thumb = $thumb->width($opts['width']);
        }

        if (isset($opts['height'])) {
            $thumb = $thumb->height($opts['height']);
        }

        return $thumb->getThumb();
    }

    # Склонение существительных ex: incline(['комментарий','комментария','комментариев'], 5)
    public function incline($words, $n)
    {
        if ($n % 100 > 4 && $n % 100 < 20) {
            return $words[2];
        }
        $a = array(2, 0, 1, 1, 1, 2);
        return $words[$a[min($n % 10, 5)]];
    }

    # Спец-класс-хелпер для упрощения валидации
    public function validator()
    {
        return new ValidatorHelper;
    }

    public function notice()
    {
        return new Notice;
    }

    # Восстановить информацию из дампа
    public static function fromDump($name)
    {
        DB::unprepared(file_get_contents(plugins_path("zen/dolphin/updates/dumps/$name.sql")));
    }

    # Восстановить информацию из списка
    public static function fromList($name)
    {
        $records = file(plugins_path("zen/dolphin/updates/dumps/$name.list"));

        $insert = [];
        foreach ($records as $record) {
            $insert[] = [
                'name' => trim($record)
            ];
        }

        DB::table($name)->insert($insert);
    }

    # Восстановить информацию с помощью скрипта
    public static function fromScript($name)
    {
        $data = null;
        $php_script_path = plugins_path("zen/dolphin/updates/dumps/$name.php");
        require $php_script_path;
        DB::table($name)->insert($data);
    }
}
