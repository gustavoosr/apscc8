<?php
declare(strict_types=1);

$base = dirname(__DIR__, 2);

require $base . '/app/Core/Autoloader.php';
require $base . '/app/Core/Exceptions.php';
\App\Core\Autoloader::register($base . '/app');

\App\Core\Config::$basePath = $base;
\App\Core\Env::load($base . '/.env');
date_default_timezone_set(\App\Core\Config::get('app.timezone', 'America/Sao_Paulo'));

if (\App\Core\Config::get('app.debug', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}
\App\Core\ExceptionHandler::register();

return $base;
