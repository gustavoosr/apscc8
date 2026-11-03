<?php
declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Middleware\WebAuthMiddleware;

/** @var \App\Core\Router $router */

$router->get('/', function () {
    Session::start();
    Response::redirect(empty($_SESSION['user_id']) ? '/login' : '/dashboard');
});

$router->get('/login', function () {
    Session::start();
    if (!empty($_SESSION['user_id'])) {
        Response::redirect('/dashboard');
    }
    View::render('login', ['title' => 'Entrar'], null);
});

/**
 * Páginas autenticadas. Cada entrada: rota => [view, título, perfis permitidos (vazio = todos)]
 */
$pages = [
    '/dashboard'     => ['dashboard', 'Dashboard', []],
    '/organizations' => ['organizations', 'Organizações', []],
    '/sectors'       => ['sectors', 'Setores', []],
    '/water'         => ['water', 'Consumo de água', []],
    '/energy'        => ['energy', 'Consumo de energia', []],
    '/waste'         => ['waste', 'Resíduos', []],
    '/waste-types'   => ['waste_types', 'Tipos de resíduos', ['GESTOR']],
    '/goals'         => ['goals', 'Objetivos e metas', []],
    '/reports'       => ['reports', 'Relatórios', ['GESTOR']],
    '/users'         => ['users', 'Usuários', ['ADMINISTRADOR']],
];

foreach ($pages as $path => [$view, $title, $roles]) {
    $router->get($path, function (Request $r) use ($view, $title, $roles, $path) {
        if ($roles && $r->user['role'] !== 'ADMINISTRADOR' && !in_array($r->user['role'], $roles, true)) {
            throw new \App\Core\ForbiddenException('Você não tem permissão para acessar esta página.');
        }
        View::render($view, ['title' => $title, 'user' => $r->user, 'path' => $path, 'csrf' => Session::csrfToken()]);
    }, [WebAuthMiddleware::class]);
}
