<?php namespace Zen\Keeper\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Zen\Keeper\Models\Settings;
use Zen\Keeper\Models\Backup;
use Zen\Keeper\Classes\Core;

class Backups extends Controller
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
        BackendMenu::setContext('Zen.Keeper', 'keeper-main', 'keeper-backups');
    }

    function getBackupsSize()
    {
        $total_size = Backup::sum('size');
        $size_limit = intval(Settings::get('size_limit'));
        $size_limit *= 1048576;

        $progress = ($total_size * 100) / $size_limit;
        $progress = round($progress, 1);

        $of = Core::formatSizeUnits($total_size);
        $to = Core::formatSizeUnits($size_limit);

        return (object) [
            'of' => $of,
            'to' => $to,
            'progress' => $progress
        ];
    }
}
