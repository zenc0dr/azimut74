<?php namespace Mcmraak\Sans\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Mcmraak\Sans\Controllers\Parser;

class Panel extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Sans', 'sans-main', 'sans-parsers');
    }

    public function index() {
        $this->addCss('/plugins/mcmraak/sans/assets/css/sans.parsers.css');
        $this->addJs('/plugins/mcmraak/sans/assets/js/sans.parsers.js');

        $info = Parser::getInfo();

        return \View::make('mcmraak.sans::parsers', ['info' => $info]);
    }
}
