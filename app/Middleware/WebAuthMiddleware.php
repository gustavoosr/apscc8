<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/** Para páginas HTML: redireciona ao login em vez de devolver 401 JSON. */
final class WebAuthMiddleware
{
    public function handle(Request $r, callable $next): mixed
    {
        Session::start();
        if (empty($_SESSION['user_id'])) {
            Response::redirect('/login');
        }
        $st = Database::pdo()->prepare('SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 AND deleted_at IS NULL');
        $st->execute([$_SESSION['user_id']]);
        $user = $st->fetch();
        if (!$user) {
            Session::destroy();
            Response::redirect('/login');
        }
        $r->user = $user;
        return $next($r);
    }
}
