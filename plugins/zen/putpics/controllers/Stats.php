<?php namespace Zen\Putpics\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use DB;

class Stats extends Controller
{
    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Zen.Putpics', 'putpics', 'stats');
    }

    public function index() {
        $users_ids = DB::table('zen_putpics_tasks')
            ->select('user_id')
            ->pluck('user_id')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
        $users = DB::table('backend_users')->whereIn('id', $users_ids)->get();

        $stats = [];

        foreach ($users as $user) {
            $success_tasks_count = DB::table('zen_putpics_tasks')
                ->where('user_id', $user->id)
                ->where('success', 1)
                ->count();
            $stats[] = [
                'user' => $user->email,
                'count' => $success_tasks_count
            ];
        }

        return view('zen.putpics::stats', ['stats' => $stats]);
    }
}
