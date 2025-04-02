<?php namespace Zen\Worker\Classes;


use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class Service
{
    # Преобразовать массив в html для изучения
    function htmlDump($array, $items_limit = 300000)
    {
        $dumper = new HtmlDumper;
        $cloner = new VarCloner();
        $cloner->setMaxItems($items_limit);
        return $dumper->dump($cloner->cloneVar($array), true);
    }

    function ddd($value)
    {
        $dump = $this->htmlDump($value);
        echo $dump;
        die;
    }
}
