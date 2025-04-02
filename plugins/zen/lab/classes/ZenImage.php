<?php namespace Zen\Lab\Classes;

use ToughDeveloper\ImageResizer\Classes\Image as Resizer;

class ZenImage
{
    function resize($image_path, $opts = [])
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
}
