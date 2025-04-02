<?php namespace Zen\Worker\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Zen\Worker\Models\Stream;
use Input;
use Flash;

class StreamController extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
        'Backend\Behaviors\ReorderController'
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Worker', 'worker-main', 'worker-streams');
    }

    function onActivity()
    {
        $source_ids = Input::get('checked');

        foreach ($source_ids as $id) {
            $source = Stream::find($id);
            $source->active = !$source->active;
            $source->save();
        }
    }
}
