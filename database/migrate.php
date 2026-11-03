<?php
declare(strict_types=1);

/**
 * Executor de migrações e seeds.
 * Uso: php database/migrate.php [--fresh] [--seed]
 *   --fresh  apaga todas as tabelas antes de migrar
 *   --seed   executa os seeds de demonstração (database/seeds/*.php)
 */
$base = require dirname(__DIR__) . '/app/Core/bootstrap.php';

use App\Core\Database;

$pdo = Database::pdo();
$opts = array_slice($argv, 1);

if (in_array('--fresh', $opts, true)) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $pdo->exec("DROP TABLE `$t`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "Tabelas removidas.\n";
}

$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
$done = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);
foreach ($files as $f) {
    $v = basename($f, '.sql');
    if (in_array($v, $done, true)) {
        continue;
    }
    // Divide por ';' no fim de linha (os arquivos não usam procedures).
    foreach (preg_split('/;\s*[\r\n]+/', file_get_contents($f)) as $stmt) {
        if (trim($stmt) !== '') {
            $pdo->exec($stmt);
        }
    }
    $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([$v]);
    echo "Migração aplicada: $v\n";
}

if (in_array('--seed', $opts, true)) {
    $seeds = glob(__DIR__ . '/seeds/*.php');
    sort($seeds);
    foreach ($seeds as $s) {
        (require $s)($pdo);
        echo 'Seed executado: ' . basename($s) . "\n";
    }
}
echo "Concluído.\n";
