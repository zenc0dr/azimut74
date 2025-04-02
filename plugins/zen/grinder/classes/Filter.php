<?php namespace Zen\Grinder\Classes;



class Filter
{
    function thumbs($image, $options)
    {
        $grinder = Grinder::open((is_object($image)) ? $image->path : $image);
        if(is_array($options)) return $grinder->magic()->getThumbs($options);
        return $grinder->magic()->options($options)->getThumb();
    }
}
