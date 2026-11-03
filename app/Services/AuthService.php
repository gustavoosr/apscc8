<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Session;
use App\Helpers\ActivityLog;

final class AuthService
{
    public function login(Request $r, string $email, string $password): array
    {
        $pdo = Database::pdo();
        $max = (int) Config::get('app.login_max_attempts', 5);
        $minutes = (int) Config::get('app.login_lockout_minutes', 10);

        $st = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > (NOW() - INTERVAL ? MINUTE)');
        $st->execute([$email, $minutes]);
        if ((int) $st->fetchColumn() >= $max) {
            throw new HttpException("Muitas tentativas. Aguarde {$minutes} minutos e tente novamente.", 429);
        }

        $st = $pdo->prepare('SELECT * FROM users WHERE email = ? AND active = 1 AND deleted_at IS NULL');
        $st->execute([$email]);
        $user = $st->fetch();

        // password_verify é executado mesmo sem usuário para equalizar o tempo de resposta.
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsalt.invalidhashxxxxxxxxxxxxxxxxxxxx';
        if (!password_verify($password, $hash) || !$user) {
            $pdo->prepare('INSERT INTO login_attempts (email, ip) VALUES (?, ?)')->execute([$email, $r->ip()]);
            throw new HttpException('E-mail ou senha incorretos.', 401);
        }

        $pdo->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$email]);
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        Session::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        unset($_SESSION['csrf']);
        ActivityLog::record($r, 'LOGIN', 'users', (int) $user['id'], (int) $user['id']);

        return [
            'user' => ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
            'csrf_token' => Session::csrfToken(),
        ];
    }

    public function logout(Request $r): void
    {
        ActivityLog::record($r, 'LOGOUT', 'users', $r->user['id'] ?? null);
        Session::destroy();
    }
}
