<?php namespace Mcmraak\Sans\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Mcmraak\Sans\Models\Resort;
use Mcmraak\Sans\Models\Group;
use View;

class Resorts extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
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

    /* One col resort list */
    public static function getResorts()
    {
        $resorts = Resort::orderBy('name')->get();

        $options = [];
        foreach ($resorts as $resort){
            $options[] = [
                'name' => $resort->name,
                'value' => $resort->id
            ];
        }

        $return = [
            'selected' => $resorts->first()->id,
            'options' => $options
        ];

        return json_encode ($return, JSON_UNESCAPED_UNICODE);
    }

    /* Nested resort list */

    public static function nestedSeparator($nest_depth)
    {
        if(!$nest_depth) return;
        $return = '';
        for($i=0;$i<$nest_depth;$i++){
            $return .='- ';
        }
        return $return;
    }

    public static function getResortsNested() // # deprecated
    {
        $groups = Group::get();

        $max_separator = 0;

        $options = [];
        foreach ($groups as $group){
            if($max_separator < $group->nest_depth) $max_separator = $group->nest_depth;
            $separator = self::nestedSeparator($group->nest_depth);
            $options[] = [
                'name' => $separator.$group->name,
                'value' => 'group_id:'.$group->id
            ];
            $resorts = $group->resorts()->orderBy('name')->orderBy('sort_order')->get();
            if($resorts){
                foreach ($resorts as $resort){
                    $separator = self::nestedSeparator($max_separator + 1);
                    $options[] = [
                        'name' => $separator.$resort->name,
                        'value' => $resort->id
                    ];
                }
            }
        }

        $return = [
            'selected' => 'group_id:'.$groups->first()->id,
            'options' => $options
        ];

        return json_encode ($return, JSON_UNESCAPED_UNICODE);
    }

    public static function resortsOptions(){
        $groups = Group::get();
        $options = [];
        foreach ($groups as $group){
            $options[] = [
                'name' => $group->name,
                'value' => 'group_id:'.$group->id
            ];
            $resorts = $group->resorts()->orderBy('name')->orderBy('sort_order')->get();
            if($resorts){
                foreach ($resorts as $resort){
                    $options[] = [
                        'name' => $resort->name,
                        'value' => $resort->id
                    ];
                }
            }
        }
        return $options;
    }
    public static function getResortsNestedHtml(){

        $groups = Group::where('parent_id', 0)->get();
        $nested_html = (string) View::make('mcmraak.sans::resorts_json', ['groups' => $groups]);

        $first_group = $groups->first();

        return json_encode ([
            'selected' => 'group_id:'.$first_group->id,
            'options' => self::resortsOptions(),
            'options_html' => $nested_html
        ], JSON_UNESCAPED_UNICODE);

    }

}