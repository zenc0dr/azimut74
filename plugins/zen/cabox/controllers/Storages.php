<?php namespace Zen\Cabox\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use View;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Zen\Cabox\Models\Storage;
use Zen\Cabox\Classes\Cabox;
use Illuminate\Support\Facades\Artisan;
use Flash;
use Log;
use Zen\Cabox\Classes\Core;

class Storages extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController'
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['zen.cabox'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Cabox', 'cabox-main', 'cabox-storages');
    }

    function onShowDump()
    {
        $core = new Core;

        $storage_id = $core->input('storage_id');
        $file_name = $core->input('filename');

        $storage = Storage::find($storage_id);
        $cache = $storage->data;
        $dump = $cache->getByFileName($file_name, true);

        $image = null;

        if($storage->images) {
            $dump['image_url'] = $cache->getImageUrl($dump['key']);
            $dump['value'] = '';

        } else {
            $dump['value'] = $this->dump($this->somethingToArray($dump['value']));
        }

        return View::make('zen.cabox::cache_dump', [
            'dump' => $dump,
            'filename' => $file_name,
        ])->render();
    }

    function somethingToArray($data)
    {
        if(is_array($data)) return $data;
        $arr = json_decode($data, 1);
        if(json_last_error() == JSON_ERROR_NONE) return $arr;
        return [$data];
    }

    function dump($value)
    {
        $dumper = new HtmlDumper;
        $cloner = new VarCloner();
        $cloner->setMaxItems(100000);
        return $dumper->dump($cloner->cloneVar($value), true);
    }

    function onCleanStorage()
    {
        $input = Input::all();
        $storage = Storage::where('code', $input['Storage']['code'])->first();
        $storage->purge();
        Flash::success('Хранилище очищено');
    }

    function onHandleStorage()
    {
        $core = new Core;
        $node_id = $core->input('node_id');
        preg_match('/-(\d+)-/', $node_id, $m);
        $node_id = $m[1];
        $storage_code = $core->input('Storage.code');
        $handlers = $core->input('Storage.handlers');
        $php_code = $handlers[$node_id]['handler_php'];


        $php_script = View::make('zen.cabox::handler_blueprint', [
            'storage_code' => $storage_code,
            'php_code' => $php_code
        ])->render();

        $script_path = storage_path('cabox_handler.php');
        file_put_contents($script_path, $php_script);

        $echo = '';

        require_once $script_path;

        Flash::success($echo);

        //Log::debug($php_code);
    }
}
