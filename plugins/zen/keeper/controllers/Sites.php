<?php namespace Zen\Keeper\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Flash;
use Http;
use Log;
use Zen\Keeper\Models\Site;
use Zen\Keeper\Classes\Core;

class Sites extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController'
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = ['zen.keeper'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Keeper', 'keeper-main', 'keeper-sites');
    }

    public function onCreateBackup()
    {
        $site_id = post('id');
        $site = Site::find($site_id);
        $domain = (new Core)->getDomain();

        $api_url = $site->url
            . '/zen/keeper/api/backup:make?security_token=' . $site->security_token
            . "&remote_domain=$domain";

        #Log::debug("keeper query: $api_url");

        $response = Http::get($api_url);
        $response = json_decode($response, true);

        if(json_last_error() != JSON_ERROR_NONE) {
            Flash::error('Error');
            return;
        }

        if(!@$response['message']) {
            Flash::error('Error');
            return;
        }

        #Log::debug("keeper answer: {$response['message']}");

        Flash::success($response['message']);
    }

}
