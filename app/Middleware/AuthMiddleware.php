<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\UnauthorizedException;

/** Exige sessão autenticada e carrega o usuário (revalidado no banco a cada requisição). */
final class AuthMiddleware
{
    public function handle(Request $r, callable $next): mixed
    {
        Session::start();
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            throw new UnauthorizedException();
        }
        $st = Database::pdo()->prepare('SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 AND deleted_at IS NULL');
        $st->execute([$id]);
        $user = $st->fetch();
        if (!$user) {
            Session::destroy();
            throw new UnauthorizedException('Sessão inválida.');
        }
        if ($user['role'] === 'ADMINISTRADOR') {
            $orgs = Database::pdo()->query('SELECT id FROM organizations WHERE deleted_at IS NULL')->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $o = Database::pdo()->prepare('SELECT ou.organization_id FROM organization_users ou JOIN organizations o ON o.id = ou.organization_id AND o.deleted_at IS NULL WHERE ou.user_id = ?');
            $o->execute([$id]);
            $orgs = $o->fetchAll(\PDO::FETCH_COLUMN);
        }
        $user['org_ids'] = array_map('intval', $orgs);
        $user['id'] = (int) $user['id'];
        $r->user = $user;
        return $next($r);
    }
}
