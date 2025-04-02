<?php

namespace Zen\Fetcher\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

class Dashboard extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Fetcher', 'main');
    }

    public function index(): string
    {
        $this->addCss(mix('css/fetcher-dashboard.css', 'plugins/zen/fetcher/assets'));
        $this->addJs(mix('js/fetcher-dashboard.js', 'plugins/zen/fetcher/assets'), ['defer' => true]);
        return '<div id="FetcherDashBoardApp"></div>';
    }
}
