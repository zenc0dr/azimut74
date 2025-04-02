<?php namespace Zen\Cabox\Classes;


class CaboxSQLite
{
    public
        $time = null;

    private
        $path,
        $lifetime;

    function __construct($options=[])
    {
        $this->path = (isset($options['path']))?$options['path']:storage_path('cabox.sqlite');
        if(isset($options['time'])) $this->time = $options['time'];
    }



    function put($key, $value) {

    }
}
