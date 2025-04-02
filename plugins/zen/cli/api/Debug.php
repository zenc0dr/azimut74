<?php namespace Zen\Cli\Api;

use Zen\Cli\Classes\Cli;

class Debug
{
    # /zen/cli/api/Debug@runTestProcess
    function runTestProcess()
    {
        $cli = new Cli;
        $cli->artisanExec("cli:test --time=1800");
    }

    # /zen/cli/api/Debug@testProcessIsLaunched
    function testProcessIsLaunched()
    {
        $cli = new Cli;
        $pid = $cli->processIsLaunched('cli:test');
        dd($pid);
    }

    # /zen/cli/api/Debug@testKillProcess
    function testKillProcess()
    {
        $cli = new Cli;
        $cli->killProcess('cli:test');
    }

    # /zen/cli/api/Debug@testOutput
    function testOutput()
    {
        $cli = new Cli;
        $cli->output = base_path('storage/logs/test_output.log');
        $cli->artisanExec("cli:test --time=1800");
    }

    # /zen/cli/api/Debug@testLogRender
    function testLogRender()
    {
        $cli = new Cli;
        echo $cli->logRender(base_path('storage/logs/test_output.log'));
    }
}
