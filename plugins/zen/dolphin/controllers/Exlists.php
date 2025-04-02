<?php namespace Zen\Dolphin\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Backend;
use Yaml;
use View;

class Exlists extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Dolphin', 'dolphin-main', 'dolphin-exlists');
    }

    function index()
    {
        $data = [
            'backend_url' => Backend::url('zen/dolphin'),
            'icons_url' => url('plugins/zen/dolphin/assets/images/icons'),
            'menu' => Yaml::parseFile(base_path('plugins/zen/dolphin/config/exlists.yaml'))
        ];

        $this->reGroup($data['menu']);

        return View::make('zen.dolphin::backend.exlists', $data)->render();
    }

    function reGroup(&$menu)
    {
        $newMenu = [];
        foreach ($menu as $slug => $item) {
            $newMenu[$item['group']][$slug] = $item;
        }
        $menu = $newMenu;
    }
}
