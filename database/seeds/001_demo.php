<?php
declare(strict_types=1);

/**
 * Dados de DEMONSTRAÇÃO (todos marcados is_demo = 1). Não representam dados reais.
 * Usuários: admin@ecomanager.local, gestor@ecomanager.local, operador@ecomanager.local
 * Senha de todos: Eco@12345  (altere em ambientes reais!)
 */
return function (PDO $pdo): void {
    mt_srand(2026);
    $pdo->exec("DELETE FROM organizations WHERE is_demo = 1 AND 0"); // seeds não removem dados existentes

    $has = $pdo->query("SELECT COUNT(*) FROM organizations WHERE is_demo = 1")->fetchColumn();
    if ($has) {
        echo "  (dados de demonstração já existem; seed ignorado)\n";
        return;
    }

    $hash = password_hash('Eco@12345', PASSWORD_DEFAULT);
    $u = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_demo) VALUES (?,?,?,?,1)');
    $u->execute(['Administrador (exemplo)', 'admin@ecomanager.local', $hash, 'ADMINISTRADOR']);
    $adminId = (int) $pdo->lastInsertId();
    $u->execute(['Gestora Ambiental (exemplo)', 'gestor@ecomanager.local', $hash, 'GESTOR']);
    $gestorId = (int) $pdo->lastInsertId();
    $u->execute(['Operador de Campo (exemplo)', 'operador@ecomanager.local', $hash, 'OPERADOR']);
    $operId = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO organizations (name, legal_id, email, phone, is_demo) VALUES (?,?,?,?,1)')
        ->execute(['Indústria Verde Ltda (exemplo)', '00.000.000/0001-00', 'contato@exemplo.invalid', '(00) 0000-0000']);
    $org = (int) $pdo->lastInsertId();
    $link = $pdo->prepare('INSERT INTO organization_users (organization_id, user_id) VALUES (?,?)');
    foreach ([$adminId, $gestorId, $operId] as $uid) {
        $link->execute([$org, $uid]);
    }

    $sectors = [];
    $s = $pdo->prepare('INSERT INTO sectors (organization_id, name, description) VALUES (?,?,?)');
    foreach (['Produção' => 'Linha de produção', 'Administrativo' => 'Escritórios', 'Refeitório' => 'Cozinha e refeitório', 'Almoxarifado' => 'Estoque e expedição'] as $n => $d) {
        $s->execute([$org, $n, $d]);
        $sectors[$n] = (int) $pdo->lastInsertId();
    }

    // Perfil base mensal por setor (água m³, energia kWh) com tendência de queda leve.
    $profile = ['Produção' => [120, 5200], 'Administrativo' => [35, 1800], 'Refeitório' => [60, 1400], 'Almoxarifado' => [20, 900]];
    $water = $pdo->prepare('INSERT INTO water_consumption (organization_id, sector_id, reference_month, quantity_m3, original_quantity, original_unit, notes, is_demo, created_by) VALUES (?,?,?,?,?,?,?,1,?)');
    $energy = $pdo->prepare('INSERT INTO energy_consumption (organization_id, sector_id, reference_month, quantity_kwh, notes, is_demo, created_by) VALUES (?,?,?,?,?,1,?)');

    $cur = new DateTimeImmutable('first day of this month');
    for ($i = 11; $i >= 0; $i--) {
        $m = $cur->modify("-$i month")->format('Y-m-01');
        $trend = 1 - (11 - $i) * 0.012; // ~ -1,2% ao mês
        foreach ($profile as $name => [$w, $e]) {
            $noise = fn () => 1 + (mt_rand(-60, 60) / 1000);
            $wq = round($w * $trend * $noise(), 3);
            $eq = round($e * (1 - (11 - $i) * 0.006) * $noise(), 3);
            if (mt_rand(0, 3) === 0) { // alguns lançamentos informados em litros
                $water->execute([$org, $sectors[$name], $m, $wq, $wq * 1000, 'L', 'Registro de exemplo', $gestorId]);
            } else {
                $water->execute([$org, $sectors[$name], $m, $wq, $wq, 'm3', 'Registro de exemplo', $gestorId]);
            }
            $energy->execute([$org, $sectors[$name], $m, $eq, 'Registro de exemplo', $operId]);
        }
    }

    // Resíduos: 3 lançamentos por mês e setor, taxa de reciclagem crescente.
    $types = $pdo->query('SELECT id, name FROM waste_types')->fetchAll(PDO::FETCH_KEY_PAIR);
    $typeId = fn (string $n) => array_search($n, $types, true);
    $dest = $pdo->query('SELECT code, id FROM waste_destinations')->fetchAll(PDO::FETCH_KEY_PAIR);
    $waste = $pdo->prepare('INSERT INTO waste_records (organization_id, sector_id, waste_type_id, destination_id, record_date, quantity_kg, notes, is_demo, created_by) VALUES (?,?,?,?,?,?,?,1,?)');
    $mix = [
        ['Papelão', 'RECICLAGEM', 140], ['Plástico filme', 'RECICLAGEM', 60], ['Alumínio', 'RECICLAGEM', 25],
        ['Resíduo de alimentos', 'COMPOSTAGEM', 90], ['Resíduo não reciclável', 'ATERRO', 150],
        ['Lâmpadas fluorescentes', 'TRATAMENTO', 6], ['Papel de escritório', 'REUTILIZACAO', 20],
    ];
    for ($i = 11; $i >= 0; $i--) {
        $month = $cur->modify("-$i month");
        $shift = (11 - $i) * 0.03; // aumenta a parcela reciclada, reduz o aterro
        foreach ($mix as [$tn, $dc, $base]) {
            $factor = match ($dc) { 'RECICLAGEM' => 1 + $shift, 'ATERRO' => 1 - $shift * 0.8, default => 1 };
            $sec = array_values($sectors)[mt_rand(0, 3)];
            $day = min(28, mt_rand(3, 27));
            $q = round($base * $factor * (1 + mt_rand(-80, 80) / 1000), 3);
            $waste->execute([$org, $sec, $typeId($tn), $dest[$dc], $month->format('Y-m-') . sprintf('%02d', $day), $q, 'Registro de exemplo', $operId]);
        }
    }

    // Objetivos e metas
    $obj = $pdo->prepare('INSERT INTO environmental_objectives (organization_id, title, description, is_demo, created_by) VALUES (?,?,?,1,?)');
    $goal = $pdo->prepare('INSERT INTO environmental_goals (objective_id, organization_id, title, metric, direction, baseline_value, target_value, start_date, due_date, is_demo) VALUES (?,?,?,?,?,?,?,?,?,1)');
    $d = fn (string $mod) => $cur->modify($mod)->format('Y-m-d');

    $obj->execute([$org, 'Reduzir o consumo de água', 'Uso racional dos recursos hídricos', $gestorId]);
    $o1 = (int) $pdo->lastInsertId();
    $goal->execute([$o1, $org, 'Reduzir o consumo mensal de água em 10%', 'WATER_M3', 'DECREASE', 235, 211.5, $d('-6 month'), $d('+6 month')]);
    $goal->execute([$o1, $org, 'Reduzir 5% da água (prazo vencido)', 'WATER_M3', 'DECREASE', 240, 228, $d('-11 month'), $d('-1 month')]);

    $obj->execute([$org, 'Aumentar a reciclagem', 'Ampliar a destinação para reciclagem', $gestorId]);
    $o2 = (int) $pdo->lastInsertId();
    $goal->execute([$o2, $org, 'Atingir 40% de resíduos reciclados', 'RECYCLING_RATE', 'INCREASE', 28, 40, $d('-9 month'), $d('+3 month')]);

    $obj->execute([$org, 'Eficiência energética', 'Reduzir consumo de energia elétrica', $gestorId]);
    $o3 = (int) $pdo->lastInsertId();
    $goal->execute([$o3, $org, 'Reduzir o consumo de energia em 8%', 'ENERGY_KWH', 'DECREASE', 9700, 8924, $d('-6 month'), $d('+6 month')]);
};
