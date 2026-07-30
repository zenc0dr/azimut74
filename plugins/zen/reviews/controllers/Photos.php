<?php namespace Zen\Reviews\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Zen\Reviews\Controllers\Traits\ManagesReviewPhotos;

class Photos extends Controller
{
    use ManagesReviewPhotos;

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Reviews', 'reviews-main', 'reviews-photos');
        $this->addCss('/plugins/zen/reviews/assets/css/review-photos-admin.css');
    }

    public function index()
    {
        $this->pageTitle = 'Фото';
        $this->vars['photos'] = $this->photoService()->paginateTiles(24);
    }
}
