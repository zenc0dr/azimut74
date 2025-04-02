<?php namespace Zen\Dolphin\Classes;

use Zen\Dolphin\Models\Folder;

class Infoblock
{
    public $page_data; # Массив данных для обработки
    public $rendered = '';

    public function __construct($page_data)
    {
        $this->page_data = $page_data;
    }

    # Что сюда входит: Код блока и информация
    # информация входит в виде массива данных, сгенерированного для всей страницы
    public function build($code)
    {
        # Очистка перед новым рендерингом
        $this->rendered = '';

        $folder = Folder::where('code', $code)->first();
        if (!$folder) {
            return;
        }

        $blocks = $folder->blocks()->active()->get();

        if (!$blocks) {
            return;
        }

        foreach ($blocks as $block) {
            $vars = $block->vars;

            $vars = str_replace(' ', '', $vars);
            $vars = explode(',', $vars);

            $input = [];

            foreach ($vars as $var) {
                if (array_key_exists($var, $this->page_data)) {
                    $input[$var] = $this->page_data[$var];
                }
            }

            if (!$input) {
                continue;
            }

            $this->renderBlock($block, $input);
        }

        return $this->rendered;
    }

    public function renderBlock($block, $data)
    {
        $this->rendered .= $block->render($data);
    }
}
