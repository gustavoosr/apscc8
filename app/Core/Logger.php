<?php
declare(strict_types=1);

namespace App\Core;

/** Logger de arquivo. Nunca registra corpo de requisições, senhas ou tokens. */
final class Logger
{
    public static function error(\Throwable $e): void
    {
        self::write('ERROR', get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    }

    public static function write(string $level, string $message): void
    {
        $dir = Config::$basePath . '/storage/logs';
        if (!is_dir($dir)) {
            return;
        }
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', sprintf("[%s] %s %s\n", date('c'), $level, $message), FILE_APPEND | LOCK_EX);
    }
}
