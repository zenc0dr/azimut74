<?php namespace Zen\Qm\Api;

use Zen\Qm\Classes\Core;
use Zen\Qm\Models\Job;
use Log;
use Illuminate\Queue\Queue;

class Jobs extends  Core
{
    function mounted()
    {
        $this->json([
            'jobs_count' => Job::count()
        ]);
    }

    function work()
    {
        $this->doWork();
        $this->json([
            'jobs_count' => Job::count()
        ]);

    }

    function doWork()
    {
        $job = Job::first();
        $payload = json_decode($job->payload);
        $class = explode('@', $payload->job);
        app($class[0])->{$class[1]}($job, (array) $payload->data);
    }

}