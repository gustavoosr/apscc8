<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Helpers\Pagination;

final class UserRepository
{
    private const COLS = 'u.id, u.name, u.email, u.role, u.active, u.created_at';

    public function paginate(Pagination $p, ?string $search, ?string $role): array
    {
        $where = ['u.deleted_at IS NULL'];
        $params = [];
        if ($search) {
            $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($role) {
            $where[] = 'u.role = ?';
            $params[] = $role;
        }
        $w = implode(' AND ', $where);
        $pdo = Database::pdo();
        $c = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $w");
        $c->execute($params);
        $st = $pdo->prepare('SELECT ' . self::COLS . " FROM users u WHERE $w ORDER BY {$p->orderBy} {$p->limitSql()}");
        $st->execute($params);
        $rows = $st->fetchAll();
        foreach ($rows as &$row) {
            $row['organization_ids'] = $this->orgIds((int) $row['id']);
        }
        return [$rows, (int) $c->fetchColumn()];
    }

    public function find(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT ' . self::COLS . ' FROM users u WHERE u.id = ? AND u.deleted_at IS NULL');
        $st->execute([$id]);
        $row = $st->fetch();
        if ($row) {
            $row['organization_ids'] = $this->orgIds($id);
        }
        return $row ?: null;
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
        $st->execute([$email, $ignoreId ?? 0]);
        return (int) $st->fetchColumn() > 0;
    }

    public function create(array $d): int
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO users (name, email, password_hash, role, active) VALUES (?,?,?,?,?)')
            ->execute([$d['name'], $d['email'], password_hash($d['password'], PASSWORD_DEFAULT), $d['role'], $d['active'] ?? 1]);
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $sets = [];
        $params = [];
        foreach (['name', 'email', 'role', 'active'] as $f) {
            if (array_key_exists($f, $d)) {
                $sets[] = "$f = ?";
                $params[] = $d[$f];
            }
        }
        if (!empty($d['password'])) {
            $sets[] = 'password_hash = ?';
            $params[] = password_hash($d['password'], PASSWORD_DEFAULT);
        }
        if ($sets) {
            $params[] = $id;
            Database::pdo()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
        }
    }

    public function softDelete(int $id): void
    {
        // E-mail é alterado para liberar o endereço para novo cadastro.
        Database::pdo()
            ->prepare("UPDATE users SET deleted_at = NOW(), active = 0, email = CONCAT('deleted_', id, '_', email) WHERE id = ?")
            ->execute([$id]);
    }

    public function orgIds(int $userId): array
    {
        $st = Database::pdo()->prepare('SELECT organization_id FROM organization_users WHERE user_id = ?');
        $st->execute([$userId]);
        return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
    }

    public function syncOrganizations(int $userId, array $orgIds): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM organization_users WHERE user_id = ?')->execute([$userId]);
        $ins = $pdo->prepare('INSERT INTO organization_users (organization_id, user_id) VALUES (?,?)');
        foreach (array_unique($orgIds) as $oid) {
            $ins->execute([$oid, $userId]);
        }
    }
}
