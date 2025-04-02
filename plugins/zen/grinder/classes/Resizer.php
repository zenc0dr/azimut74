<?php namespace Zen\Grinder\Classes;


class Resizer
{

    private string $src;
    private string $dest;
    private object $options;
    private bool $magic = false;

    public function __construct($path)
    {
        $this->src = $path;
    }

    public function resize($options)
    {
        if (!@$options['width']) {
            $options['width'] = 0;
        }
        if (!@$options['height']) {
            $options['height'] = 0;
        }
        if (!@$options['extension']) {
            $options['extension'] = 'jpg';
        }
        if (!@$options['mode']) {
            $options['mode'] = 'auto';
        }
        if (!@$options['quality']) {
            $options['quality'] = 100;
        }

        $this->options = (object) $options;
        return $this;
    }

    public function magic()
    {
        $this->magic = true;
        return $this;
    }

    public function save($dest = null)
    {
        if ($dest) {
            $this->dest = $dest;
        } else {
            $this->dest = $this->getDestFromSrc();
        }

        if ($this->magic) {
            $this->magicResize();
        } else {
            $this->imageResize();
        }

        return $this;
    }

    private function getDestFromSrc()
    {
        $src_ext = pathinfo($this->src, PATHINFO_EXTENSION);
        if ($src_ext == $this->options->extension) {
            return $this->src;
        }
        return str_replace('.' . $src_ext, '.' . $this->options->extension, $this->src);
    }

    private function imageResize()
    {

        $this->imageResizer(
            $this->src,
            $this->dest,
            $this->options->width,
            $this->options->height,
            $this->options->quality
        );
    }

    private function imageResizer($src, $dest, $width, $height, $quality)
    {
        if (!file_exists($src)) {
            return;
        }

        $size = getimagesize($src);

        # Если не указана ширина
        if (!$width) {
            $width = round($size[0] * $height / $size[1]);
        }

        # Если не указана высота
        if (!$height) {
            $height = round($size[1] * $width / $size[0]);
        }

        if ($size === false) {
            return;
        }

        $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
        $icfunc = "imagecreatefrom" . $format;

        if (!function_exists($icfunc)) {
            return false;
        }

        $x_ratio = $width / $size[0]; # 2000 / 2560 = 0,78125
        $y_ratio = $height / $size[1]; # 1125 / 1440 = 0,78125

        $ratio = min($x_ratio, $y_ratio);
        $use_x_ratio = ($x_ratio == $ratio);

        $new_width = $use_x_ratio ? $width : floor($size[0] * $ratio);
        $new_height = !$use_x_ratio ? $height : floor($size[1] * $ratio);
        $new_left = $use_x_ratio ? 0 : floor(($width - $new_width) / 2);
        $new_top = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);

        $isrc = $icfunc($src);

        # Создаётся подложка
        $idest = imagecreatetruecolor($width, $height);
        imagefill($idest, 0, 0, 0xFFFFFF);

        imagecopyresampled(
            $idest, # Ресурс целевого изображения.
            $isrc, # Ресурс исходного изображения.
            $new_left, # x-координата результирующего изображения.
            $new_top,  # y-координата результирующего изображения.
            0, # x-координата исходного изображения.
            0, # y-координата исходного изображения.
            $new_width, # Результирующая ширина.
            $new_height, # Результирующая высота.
            $size[0], # Ширина исходного изображения.
            $size[1] # Высота исходного изображения.
        );

        //imagejpeg($idest, $dest, $quality);

        $postfix = pathinfo($dest, PATHINFO_EXTENSION);

        if ($postfix == 'jpg') {
            $postfix = 'jpeg';
        }

        $saveFunc = 'image' . $postfix;

        if (!function_exists($saveFunc)) {
            return;
        }

        $saveFunc($idest, $dest, $quality);

        imagedestroy($isrc);
        imagedestroy($idest);
    }

    private function magicResize()
    {
        $this->magicResizer(
            $this->src,
            $this->dest,
            $this->options->width,
            $this->options->height,
            $this->options->quality
        );
    }

    # Необходимо доустановить следующие пакеты
    # apt install imagemagick
    # apt install webp

    private function magicResizer($src, $dest, $width, $height, $quality = 100)
    {
        if (!file_exists($src)) {
            return false;
        }

        $size = getimagesize($src);

        # Если не указана ширина
        if (!$width) {
            $width = round($size[0] * $height / $size[1]);
        }

        # Если не указана высота
        if (!$height) {
            $height = round($size[1] * $width / $size[0]);
        }

        # Расширение целевого файла
        $dest_ext = pathinfo($dest, PATHINFO_EXTENSION);

        # Объявление дополнительнх опций
        $extra_options = '';

        # Если сохранять в webp
        if ($dest_ext == 'webp') {
            $extra_options = '-define webp:lossless=true ';
        }

        $shell_command = "convert -resize {$width}X{$height} -quality $quality $extra_options$src $dest";

        exec($shell_command);
    }
}
