<?php namespace Zen\Putpics\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use BackendAuth;
use Event;
use Zen\Putpics\Classes\Helpers;
use Zen\Putpics\Models\Task;
use Flash;
use Input;
use DB;

class Tasks extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController'
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'putpics.main'
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Putpics', 'putpics-main');

        Event::listen('backend.page.beforeDisplay', function ($backendController, $action, $params) {
            if ( $action !== 'update') {
                return;
            }
            $model_id = intval($params[0]);
            $model = Task::find($model_id);

            if ($model->success) {
                return;
            }

            if (intval($model->user_id) !== 0 && intval($model->user_id) !== intval(BackendAuth::id())) {
                abort(404);
            }
        });
    }

    public function listExtendQuery($query)
    {
        $term = Input::get('listToolbarSearch.term');
        if ($term) {
            $ids = [];
            $ids = array_merge(
                $ids,
                $this->idByEntities(
                    'Mcmraak\Rivercrs\Models\Cabins',
                    $term, 'category'
                ),
            );
            $ids = array_merge(
                $ids,
                $this->idByEntities(
                    'Mcmraak\Rivercrs\Models\Kpage',
                    $term
                ),
            );
            $ids = array_merge(
                $ids,
                $this->idByEntities(
                    'Mcmraak\Rivercrs\Models\Motorships',
                    $term
                ),
            );
            $ids = array_merge(
                $ids,
                $this->idByEntities(
                    'Srw\Catalog\Models\Items',
                    $term
                ),
            );
            $ids = array_merge(
                $ids,
                $this->idByEntities(
                    'Zen\Dolphin\Models\City',
                    $term
                ),
            );
            $ids = array_merge(
                $ids,
                $this->idByEntities(
                    'Zen\Dolphin\Models\Region',
                    $term
                ),
            );
            $ids = array_merge(
                $ids,
                $this->idByEntities(
                    'Zen\Dolphin\Models\Hotel',
                    $term
                ),
            );
            $ids = array_merge(
                $ids,
                $this->idByEntities(
                    'Zen\Dolphin\Models\Page',
                    $term
                ),
            );
            $ids = array_merge(
                $ids,
                $this->idByEntities(
                    'Zen\Dolphin\Models\Tour',
                    $term
                ),
            );
            $ids = array_values(array_unique($ids));

            if ($ids) {
                $query->whereIn('id', $ids);
            }
        }

        $query->where(function ($q) {
            $q->orWhere('success', 1);
            $q->orWhere('user_id', 0);
            $q->orWhere('user_id', BackendAuth::id());
        });
    }

    public function idByEntities($entity, $search_text, $field = 'name')
    {
        $entity_ids = app($entity)
            ->where($field, 'like', "%$search_text%")
            ->select('id')
            ->get()
            ->pluck('id')
            ->toArray();

        if (!$entity_ids) {
            return [];
        }

        return DB::table('zen_putpics_tasks')
            ->where('attachment_type', $entity)
            ->whereIn('attachment_id', $entity_ids)
            ->select('id')
            ->get()
            ->pluck('id')
            ->toArray();
    }

    public function onTake()
    {
        $model_id = $this->params[0];
        $model = Task::find($model_id);
        $model->user_id = BackendAuth::id();
        $model->not_found = 0;
        $model->success = 0;
        $model->save();
    }

    public function getProgress()
    {
        $success = Task::where('success', 1)->count();
        $total = Task::count();

        $progress = ($success * 100) / $total;
        $progress = round($progress, 1);

        return (object) [
            'of' => $success,
            'to' => $total,
            'progress' => $progress
        ];
    }

    public function onTakeMany()
    {
        $source_ids = Input::get('checked');

        foreach ($source_ids as $id) {
            $source = Task::find($id);
            $source->user_id = BackendAuth::id();
            $source->save();
        }
    }

    public function onCleanRecords()
    {
        $model_id = $this->params[0];
        $model = Task::find($model_id);
        $files = DB::table('system_files')
            ->where('attachment_type', $model->attachment_type)
            ->where('attachment_id', $model->attachment_id)
            ->get();

        foreach ($files as $file) {
            $spliced_file_name = Helpers::getSlicedPath($file->disk_name);
            $file_path = storage_path('app/uploads/public/'. $spliced_file_name);
            if (!file_exists($file_path)) {
                DB::table('system_files')->where('id', $file->id)->delete();
            }
        }


        Flash::success('Записи из БД удалены');
    }
}
