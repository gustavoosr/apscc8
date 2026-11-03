<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\ForbiddenException;
use App\Core\Request;

/** Uso: RoleMiddleware:ADMINISTRADOR,GESTOR (ADMINISTRADOR sempre permitido). */
final class RoleMiddleware
{
    public function handle(Request $r, callable $next, string ...$roles): mixed
    {
        $role = $r->user['role'] ?? '';
        if ($role !== 'ADMINISTRADOR' && !in_array($role, $roles, true)) {
            throw new ForbiddenException();
        }
        return $next($r);
    }
}
