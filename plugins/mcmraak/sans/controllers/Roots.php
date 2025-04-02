<?php namespace Mcmraak\Sans\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Mcmraak\Sans\Models\Root;
use Mcmraak\Sans\Models\Resort;
use Log;

class Roots extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'    ];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'mcmraak.sans'
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Sans', 'sans-main', 'sans-resorts');
    }

    public static function getRootGroups($root_id)
    {
        $root = Root::find($root_id);
        if(!$root) return;
        $root_groups = $root->resortgroups;
        if(!$root_groups->count()) return;

        $groups_ids = $root_groups->pluck('id')->toArray();

        $resorts = Resort::whereIn('group_id',$groups_ids)->get();

        $return = [];
        foreach ($resorts as $resort)
        {
            $return[] = [
                'id'   => $resort->id,
                'name' => $resort->name,
            ];
        }

        return json_encode ($return, JSON_UNESCAPED_UNICODE);
    }
}
