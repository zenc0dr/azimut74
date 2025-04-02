<?php namespace Zen\Dolphin\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Zen\Dolphin\Models\Tour;
use Flash;

class Tours extends Controller
{
    public $implement = [
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.FormController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'zen.dolphin.main'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->addCss('/plugins/zen/dolphin/assets/css/controllers.tours.css');
        BackendMenu::setContext('Zen.Dolphin', 'dolphin-main', 'dolphin-exlists');
    }

    public function onCleanImages()
    {
        $model_id = $this->params[0];
        $tour = Tour::find($model_id);
        foreach ($tour->images as $image) {
            $image->delete();
        }
        Flash::success('Изображения удалены');
    }
}
