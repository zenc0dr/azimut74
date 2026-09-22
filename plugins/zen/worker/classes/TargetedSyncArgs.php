<?php namespace Zen\Worker\Classes;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class TargetedSyncArgs
{
    public static function apply(Command $cmd, array &$parseArgs, array &$transferArgs): ?string
    {
        $ids = $cmd->option('cruise_ids') ?: $cmd->option('handle_only');
        $ships = $cmd->option('ship_ids');
        if ($ids) {
            $parseArgs['--cruise_ids'] = $ids;
            $parseArgs['--handle_only'] = $ids;
            $transferArgs['--handle_only'] = $ids;
        }
        if ($ships) {
            $parseArgs['--ship_ids'] = $ships;
        }
        return ($ids !== null && $ids !== '') ? (string) $ids : null;
    }

    public static function transferHandleArgs(?string $handleOnly): array
    {
        if ($handleOnly === null || $handleOnly === '') {
            return [];
        }
        return ['--handle_only' => $handleOnly];
    }

    public static function optionDefs(): array
    {
        return [
            ['cruise_ids', null, InputOption::VALUE_OPTIONAL, 'Только круизы eds_id через запятую', null],
            ['ship_ids', null, InputOption::VALUE_OPTIONAL, 'Только теплоходы источника через запятую', null],
        ];
    }
}
