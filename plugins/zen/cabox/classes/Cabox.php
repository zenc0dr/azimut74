<?php namespace Zen\Cabox\Classes;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use File;
use Zen\Cabox\Models\Storage;
use Log;

class Cabox extends Core
{
    public $options, $code;
    private $lifetime;

    function __construct($options=[])
    {
        if(is_array($options)) {
            $defaults = [
                'path' => storage_path('framework/cache'), # Путь до хранилища
                'time' => null, # Время актуальности,
                'one_folder' => null, # Если true, папки не разделяются на подпапки
                'compress' => true, # Включить сжатие
            ];

            $this->options = array_merge($defaults, $options);
        } else {
            $this->code = $options;
            # Строка вместо массива означает код, например $options = 'dolphin.parsers'
            $this->options = Storage::getOptions($options);
        }


        $this->options['path'] = $this->renderPath($this->options['path']);
    }

    function purge()
    {
        if(!$this->code) return;
        $storage = Storage::where('code', $this->code)->first();
        if(!$storage) return;
        $storage->purge();
    }

    function put($key, $value, $md5=true)
    {
        $file_name = $this->getPath($key, $md5);

        $value = [
            'time' => time(),
            'key' => $key,
            'value' => $value
        ];

        if($this->options['images']) {
            $value = serialize($value);
        } else {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            if($this->options['compress']) {
                $value = gzdeflate($value, 9);
            }
        }

        if(!file_exists($file_name)) {
            if(!file_exists(dirname($file_name)))
                mkdir(dirname($file_name), 0777, true);
            file_put_contents($file_name, $value);
        } else {
            file_put_contents($file_name, $value);
        }
    }

    function get($key, $time=null, $md5=true, $full_data = false)
    {

        $file_name = $this->getPath($key, $md5);

        if(!file_exists($file_name)) {
            return false;
        }

        if($time === null || $time === false) $time = $this->options['time'];

        if($time) {
            $lifetime = time() - filectime($file_name);
            $this->lifetime = $lifetime;
            if($lifetime > $time*60) {
                unlink($file_name);
                return false;
            }
        }

        $data = file_get_contents($file_name);

        if($this->options['images']) {
            $data = unserialize($data);
        } else {
            if($this->options['compress']) {
                $data = gzinflate($data);
            }
            $data = json_decode($data, 1);
        }

        if($full_data) {
            $data['file_size'] = $this->formatSizeUnits(filesize($file_name));
            $data['file_path'] = $file_name;
        } else {
            $data = $data['value'];
        }

        return $data;
    }

    function getImageUrl($key)
    {
        $ext = '.' . pathinfo($key)['extension'];

        $md5key = md5($key);

        return env('APP_URL')."/zen/cabox/image/{$this->options['id']}/$md5key$ext";
    }

    static function getImage($storage_id, $key)
    {
        $key = preg_replace('/\.jpg|\.png|\.gif$/', '', $key);

        $cabox = new self(Storage::getOptions($storage_id));
        $image = $cabox->get($key, null, false);

        if($image == 'none') {
            $image = file_get_contents(base_path('plugins/zen/cabox/assets/images/nophoto.png'));
        }

        if(!$image) {
            $image = file_get_contents(base_path('plugins/zen/cabox/assets/images/in_process.jpg'));
        }

        return response($image)->header('Content-Type', 'image');
    }

    function del($key)
    {
        $file_name = $this->getPath($key);
        if(!file_exists($file_name)) {
            return null;
        }
        unlink($file_name);
    }

    function getObject($key, $time=null)
    {
        return (object) [
            'data' => $this->get($key, $time),
            'lifetime' => $this->lifetime
        ];
    }

    function getByFileName($file_name, $full_data=false)
    {
        return $this->get($file_name, false, false, $full_data);
    }

    private function getPath($key, $md5=true)
    {
        if($md5) {
            $filename = md5($key);
        } else {
            $filename = $key;
        }


        if(intval($this->options['one_folder'])) {
            return $this->options['path'].'/'.$filename;
        }

        return $this->options['path'].'/'.implode('/', array_slice(str_split($filename, 3), 0, 3)) . '/' . $filename;
    }

    function storageSize()
    {
        return [
            'size' => $this->formatSizeUnits($this->folderSize($this->options['path'])),
            'count_files' => $this->count_files,
            'count_dirs' => $this->count_dirs,
        ];
    }

    protected $count_files = 0, $count_dirs = 0;
    protected function folderSize($directory)
    {
        if (count(scandir($directory)) == 2) {
            return 0;
        }

        $size = 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if($file->isFile()) {
                $this->count_files++;
            } else {
                $this->count_dirs++;
            }
            $size += $file->getSize();
        }

        return $size;
    }

    protected function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    function storageItems(){

        $files = File::allFiles($this->options['path']);

        $list = [];
        foreach ($files as $file) {
            if($file->getSize() < 1000000) {
                $cacheFile = $file->getContents();

                if($this->options['images']) {
                    $cacheFile = unserialize($cacheFile);
                } else {
                    if($this->options['compress']) {
                        $cacheFile = gzinflate($cacheFile);
                    }
                    $cacheFile = json_decode($cacheFile, 1);
                }

                $list[] = [
                    'key' => $cacheFile['key'],
                    'filename' => $file->getFileName(),
                    'time' => $cacheFile['time'],
                    'created_at' => date('d.m.Y H:i:s', $cacheFile['time'])
                ];
            } else {
                $list[] = [
                    'key' => 'Размер больше 1 мб '.$file->getSize(),
                    'filename' => $file->getFileName(),
                    'time' => $file->getMTime(),
                    'created_at' => date('d.m.Y H:i:s', $file->getMTime()),
                ];
            }
        }
        return $list;
    }

    function handleItems($function)
    {
        $files = File::allFiles($this->options['path']);
        foreach ($files as $file) {
            $cacheFile = $file->getContents();
            if($this->options['compress']) {
                $cacheFile = gzinflate($cacheFile);
            }
            $cacheFile = json_decode($cacheFile, 1);
            $function($cacheFile, $file);
        }
    }
}
