<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\ForbiddenException;
use App\Core\Request;
use App\Core\Session;

/** Valida o token CSRF (header X-CSRF-Token) em métodos que alteram estado. */
final class CsrfMiddleware
{
    public function handle(Request $r, callable $next): mixed
    {
        if (in_array($r->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            Session::start();
            $sent = $r->header('X-CSRF-Token') ?? '';
            $real = $_SESSION['csrf'] ?? '';
            if ($real === '' || !hash_equals($real, $sent)) {
                throw new ForbiddenException('Token CSRF inválido ou ausente.');
            }
        }
        return $next($r);
    }
}
