<?php namespace Zen\Worker\Classes;

/**
 * Уведомления о работе парсеров/transfer в Rocket.Chat (incoming webhook).
 * Не бросает исключения наружу — сбой канала не должен ломать CLI.
 */
class WorkerNotifier
{
    public static function notify(string $message): void
    {
        try {
            RocketBot::send($message);
        } catch (\Throwable $e) {
            error_log('WorkerNotifier: ' . $e->getMessage());
        }
    }
}
