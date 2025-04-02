<?php namespace Mcmraak\Blocks\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Mcmraak\Blocks\Models\Gallery;
use Mcmraak\Blocks\Models\Style;
use Twig;

class Galleries extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController', 'Backend\Behaviors\FormController', 'Backend\Behaviors\ReorderController'];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Blocks', 'blocks-main', 'blocks-galleries');
    }

    public static function getGallery($input)
    {
        $field = $input['field'] ?? null;
        $code = $input['code'] ?? null;
        $style = $input['style'] ?? null;

        if (!$field || !$code) {
            return null;
        }

        if ($field === 'id') {
            $gallery = Gallery::find($code);
        }
        if ($field === 'code') {
            $gallery = Gallery::where('code', $code)->first();
        }

        $images = $gallery->images()->get();

        if ($style) {
            $template = Style::where('code', $style)->first();
            return Twig::parse($template->html, ['images' => $images]);
        } else {
            $template = $gallery->style;
            return Twig::parse($template->html, ['images' => $images]);
        }

    }
}
