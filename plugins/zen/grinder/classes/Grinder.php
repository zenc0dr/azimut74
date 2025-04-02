<?php namespace Zen\Grinder\Classes;

use System\Models\File;
use DB;

class Grinder
{
    public
        $src_path,
        $src_name,
        $src_disk_name,
        $src_dir,
        $src_ext,
        $base_path,
        $src_not_exist = false,
        $width = 800,
        $height,
        $quality = 80,
        $extension,
        $mode,
        $magic;

    # В данном методе присваивается $this->file_path
    public static function open($input)
    {
        $self = new self;
        if ($input instanceof File) {
            # Если это экземпляр модели мы точно знаем его path
            $src_path = $input->getLocalPath();
        } else {
            # Если это URL то верю что файлы лежат в storage/app иначе ты еблан
            if (strpos($input, 'http') === 0) {
                $before_count = strpos($input, 'storage/app');
                $src_path = base_path(substr($input, $before_count));
            }
            # Это точный path файла
            else {
                $src_path = $input;
            }
        }

        # Если файл не существует
        if (!file_exists($src_path) || is_dir($src_path)) {
            $self->src_not_exist = true;
            return $self;
        }

        # Путь до исходника
        $self->src_path = $src_path;

        $pathinfo = pathinfo($src_path);

        # Имя файла без расширения ex: big_land
        $self->src_name = $pathinfo['filename'];

        # Расширение файла ex: jpg, jpeg, png, gif
        $self->src_ext = $pathinfo['extension'];

        if ($self->src_ext === 'jpeg') {
            $self->src_ext = 'jpg';
        }

        # Полное имя файла ex: big_land.png
        $self->src_disk_name = $pathinfo['basename'];

        $self->src_dir = str_replace('/' . $self->src_disk_name, '', $src_path);

        return $self;
    }

    # Активация библиотеки imagemagic
    public function magic()
    {
        $this->magic = true;
        return $this;
    }

    # Установка опций
    public function options($options)
    {
        if ($this->src_not_exist) {
            return $this;
        }

        if (is_string($options)) {
            $options = $this->stringToOptions($options);
        }

        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    # Строка в опции
    private function stringToOptions($options_string)
    {
        $options = explode('.', $options_string);
        $array = [];
        foreach ($options as $option) {
            $key = substr($option, 0, 1);
            $value = substr($option, 1);
            if ($key == 'w') {
                $array['width'] = intval($value);
            }
            if ($key == 'h') {
                $array['height'] = intval($value);
            }
            if ($key == 'q') {
                $array['quality'] = intval($value);
            }
            if ($key == 'e') {
                $array['extension'] = $value;
            }
            if ($key == 'm') {
                $array['mode'] = $value;
            }
        }
        return $array;
    }

    # Опции в строку
    private function optionsToString($options_array)
    {
        $options = [];
        foreach ($options_array as $key => $value) {
            $key = $key = substr($key, 0, 1);
            $options[] = $key . $value;
        }

        return join('.', $options);
    }

    # Установка ширины превью
    public function width($width)
    {
        $this->width = $width;
        return $this;
    }

    # Установка высоты превью
    public function height($height)
    {
        $this->height = $height;
        return $this;
    }

    # Установка качества сжатия
    public function quality($quality)
    {
        $this->quality = $quality;
        return $this;
    }

    # Установка расширения превью
    public function extension($extension)
    {
        $this->extension = $extension;
        return $this;
    }

    # Установка режима преобразования
    public function mode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    # Генеральный метод ресайза
    private function getThumbMethod($width, $height = null, $quality = null, $extension = null, $mode = null)
    {
        $quality = $quality ?? $this->quality;
        $extension = $extension ?? $this->extension ?? 'jpg';
        $mode = $mode ?? $this->mode ?? 'auto';

        $options = [
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
            'extension' => $extension,
            'mode' => $mode
        ];

        $thumb_path = $this->src_dir . '/' . $this->src_name . '_grinder_';


        if ($width) {
            $thumb_path .= "w$width";
        }
        if ($height) {
            $thumb_path .= "h$height";
        }
        if ($quality) {
            $thumb_path .= "q$quality";
        }
        if ($mode) {
            $thumb_path .= $mode;
        }
        $thumb_path .= ".$extension";

        if (str_contains($thumb_path, 'storage/app/media')) {
            $thumb_path = str_replace('storage/app/media', 'storage/app/media/grinder_thumbs', $thumb_path);

            # Создать папку
            if (!file_exists(dirname($thumb_path))) {
                mkdir(dirname($thumb_path), 0777, true);
            }
        }

        # Только относительный путь в качестве ключа
        $thumb_url = '/' . substr($thumb_path, strpos($thumb_path, 'storage/'));

        if (file_exists($thumb_path)) {
            return $thumb_url;
        }

        if ($this->magic) {
            self::original($this->src_path)->magic()->resize($options)->save($thumb_path);
        } else {
            self::original($this->src_path)->resize($options)->save($thumb_path);
        }

        if (!file_exists($thumb_path)) {
            return;
        }
        return $thumb_url;
    }

    # Метод получения одной превью
    public function getThumb()
    {
        if ($this->src_not_exist) {
            return;
        }
        return $this->getThumbMethod($this->width, $this->height, $this->quality, $this->extension, $this->mode);
    }

    # Метод получения группы превью
    public function getThumbs($options_array)
    {
        if ($this->src_not_exist) {
            return;
        }
        $output = [];
        foreach ($options_array as $options) {
            $options_array = null;
            $options_string = null;

            if (is_string($options)) {
                $options_string = $options;
                $options_array = $this->stringToOptions($options);
            } else {
                $options_array = $options;
                $options_string = $this->optionsToString($options);
            }

            $output[$options_string] = $this->getThumbMethod(
                $options_array['width'],
                @$options_array['height'],
                @$options_array['quality'],
                @$options_array['extension'],
                @$options_array['mode'],
            );
        }

        return $output;
    }

    # Изменить размер оригинала (Только для объектов File (system_files))
    public static function resizeOriginal(File $file, $options)
    {
        $width = @$options['width'] ?? 0;
        $height = @$options['height'] ?? 0;
        $options['quality'] = @$options['quality'] ?? 80;
        $extension = @$options['extension'] ?? 'jpg';
        $mode = @$options['mode'] ?? 'auto';
        $file_path = $file->getLocalPath();

        # Расширение не изменилось
        if ($extension == $file->getExtension()) {
            self::original($file_path)->resize($options)->save();
            return $file_path;
        } else {
            # Если нужно увеличить размер в случае смены расширения
            if ($mode == 'auto') {
                $image_size = getimagesize($file_path);
                $image_width = $image_size[0];
                $image_height = $image_size[1];
                if ($width > $image_width) {
                    $width = $image_width;
                }
                if ($height > $image_height) {
                    $height = $image_height;
                }
            }

            $old_disk_name = $file->disk_name;
            $old_extension = $file->getExtension();

            $new_disk_name = str_replace(".$old_extension", ".$extension", $old_disk_name);
            $new_file_path = self::getStoragePath($new_disk_name);
            $new_file_name = str_replace(".$old_extension", ".$extension", $file->file_name);

            self::original($file_path)->resize($options)->save($new_file_path);


            if (file_exists($new_file_path)) {
                # Удаляем старый файл
                unlink($file_path);

                # Обновляем запись в БД
                DB::table('system_files')->where('id', $file->id)->update([
                    'disk_name' => $new_disk_name,
                    'file_name' => $new_file_name,
                    'file_size' => filesize($new_file_path),
                    'content_type' => str_replace($old_extension, $extension, $file->content_type),
                ]);
            } else {
                echo "Ресайз не получился: $file_path > $new_file_path\n";
                die;
            }

            return $new_file_path;
        }
    }

    static function getStoragePath($disk_name)
    {
        return base_path('storage/app/uploads/public/' . implode('/', array_slice(str_split($disk_name, 3), 0, 3)) . '/' . $disk_name);
    }

    private function getLocalPathFromUrl($url)
    {
        return base_path(str_replace(env('APP_URL') . '/', '', $url));
    }

    static function original($path)
    {
        return new Resizer($path);
    }
}
