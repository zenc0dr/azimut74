<?php namespace Mcmraak\Blocks;

use System\Classes\PluginBase;
use Mcmraak\Blocks\Models\Code;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'Mcmraak\Blocks\Components\Block'  => 'Block',
            'Mcmraak\Blocks\Components\Map'  => 'Map',
			'Mcmraak\Blocks\Components\Carousel'  => 'Carousel',
            'Mcmraak\Blocks\Components\Gallery'  => 'Gallery',
            'Mcmraak\Blocks\Components\Injector'  => 'Injector',
        ];
    }

    public function registerSettings()
    {
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'mutator' => [$this, 'mutate']
            ]
        ];
    }

    public function mutate($text)
    {
        $codes = Code::where('active', 1)->get();
        foreach ($codes as $code)
        {
            $text = str_replace("[[{$code->code}]]",$code->replace, $text);
        }
        $text = preg_replace('/\[\[[a-zа-я0-9 \.,!_]+\]\]/ui','', $text);
        return $text;
    }
}
