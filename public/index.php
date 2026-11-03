<?php
declare(strict_types=1);

// Front controller: toda requisição passa por aqui.
$base = require dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\Request;
use App\Core\Router;

// Servidor embutido do PHP: entrega arquivos estáticos diretamente.
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($p !== '/' && is_file(__DIR__ . $p)) {
        return false;
    }
}

header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');

$router = new Router();
require $base . '/routes/api.php';
require $base . '/routes/web.php';

$router->dispatch(Request::capture());
