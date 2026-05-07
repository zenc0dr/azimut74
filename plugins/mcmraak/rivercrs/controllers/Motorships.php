<?php namespace Mcmraak\Rivercrs\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Mcmraak\Rivercrs\Classes\ReviewsWidget;
use Mcmraak\Rivercrs\Controllers\Traits\HasReviewBindings;

class Motorships extends Controller
{
    use HasReviewBindings;

    const REVIEW_ENTITY_TYPE = ReviewsWidget::ENTITY_MOTORSHIP;

    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = ['mcmraak.rivercrs.motorships'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-motorships');
        $this->addJs("/plugins/mcmraak/rivercrs/assets/node_modules/vue/dist/vue.min.js");
    }
}
