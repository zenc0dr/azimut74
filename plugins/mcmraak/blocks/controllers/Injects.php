<?php namespace Mcmraak\Blocks\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use DB;
use Twig;
use Mcmraak\Blocks\Models\Template;

use Exception;

class Injects extends Controller
{
    public $implement = [        'Backend\Behaviors\ListController',        'Backend\Behaviors\FormController'    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'blocks.usage'
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Blocks', 'blocks-main', 'blocks-injects');
    }

    public static function getIjects()
    {
        $scope_id = Input::get('scope_id');

        if (!$scope_id) {
            return null;
        }

        $blocks = DB::table('mcmraak_blocks_injects_scopes as pivot')
            ->where('pivot.scope_id', $scope_id)
            ->where('blocks.active', 1)
            ->join(
                'mcmraak_blocks_injects as blocks',
                'pivot.inject_id',
                '=',
                'blocks.id'
            )
            ->select(
                'pivot.sequence as sequence',
                'blocks.template_id as template_id',
                'blocks.image as image',
                'blocks.html as html',
                'blocks.link as link',
                'blocks.code as code'
            )
            ->get();

        $return = [];
        foreach ($blocks as $block){
            $template = Template::find($block->template_id);

            $code = ($block->code) ? eval($block->code) : null;

            if (substr($code, 0, 1) === '{') {
                $return[] = [
                    'sequence' => $block->sequence,
                    'html' => $code,
                ];
            } else {
                $return[] = [
                    'sequence' => $block->sequence,
                    'html' => Twig::parse($template->code,[
                        'image' => $block->image,
                        'html' => $block->html,
                        'link' => $block->link,
                        'code' => $code,
                    ])
                ];
            }
        }

        echo json_encode ($return, JSON_UNESCAPED_UNICODE);
    }

}
