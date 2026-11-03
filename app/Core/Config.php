<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $cache = [];
    public static string $basePath = '';

    public static function get(string $key, mixed $default = null): mixed
    {
        [$file, $item] = array_pad(explode('.', $key, 2), 2, null);
        if (!isset(self::$cache[$file])) {
            $path = self::$basePath . "/config/$file.php";
            self::$cache[$file] = is_file($path) ? require $path : [];
        }
        return $item === null ? self::$cache[$file] : (self::$cache[$file][$item] ?? $default);
    }
}
