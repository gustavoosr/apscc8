<?php
declare(strict_types=1);
/**
 * Testes de integração da API (servidor deve estar rodando).
 * Uso: php tests/api_test.php [http://127.0.0.1:8080]
 * Requer os dados de demonstração (php database/migrate.php --fresh --seed).
 */
$base = $argv[1] ?? 'http://127.0.0.1:8080';
$pass = $fail = 0;

final class Client {
    public string $cookie = '';
    public string $csrf = '';
    public function __construct(private string $base) {}
    public function call(string $m, string $path, ?array $body = null, bool $csrf = true, array $headers = []): array {
        $ch = curl_init($this->base . '/api/v1' . $path);
        $h = ['Accept: application/json', ...$headers];
        if ($body !== null) $h[] = 'Content-Type: application/json';
        if ($csrf && $this->csrf) $h[] = 'X-CSRF-Token: ' . $this->csrf;
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $m, CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $h, CURLOPT_COOKIE => $this->cookie, CURLOPT_POSTFIELDS => $body !== null ? json_encode($body) : null]);
        $raw = curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $head = substr($raw, 0, $size);
        if (preg_match('/Set-Cookie: (ECOSESSID=[^;]+)/i', $head, $mm)) $this->cookie = $mm[1];
        return [$status, json_decode(substr($raw, $size), true), substr($raw, $size)];
    }
    public function login(string $email, string $pw = 'Eco@12345'): array {
        $this->cookie = ''; $this->csrf = '';
        $r = $this->call('POST', '/auth/login', ['email' => $email, 'password' => $pw], false);
        if ($r[0] === 200) $this->csrf = $r[1]['data']['csrf_token'];
        return $r;
    }
}
function check(string $name, bool $ok, string $extra = ''): void {
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? "  ok   " : "  FAIL ") . $name . ($ok ? '' : "  -> $extra") . "\n";
}

function cleanup(): void {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecomanager;charset=utf8mb4', 'ecomanager', 'EcoMgr#2026dev');
    $pdo->exec("DELETE FROM energy_consumption WHERE reference_month < '2020-01-01'");
    $pdo->exec("DELETE FROM water_consumption WHERE reference_month < '2021-01-01'");
    $pdo->exec("DELETE FROM waste_records WHERE record_date < '2019-01-01'");
    $pdo->exec("DELETE FROM environmental_goals WHERE title IN ('Meta teste')");
    $pdo->exec("DELETE FROM sectors WHERE organization_id IN (SELECT id FROM organizations WHERE name = 'Outra Organização Teste')");
    $pdo->exec("DELETE FROM organization_users WHERE organization_id IN (SELECT id FROM organizations WHERE name = 'Outra Organização Teste')");
    $pdo->exec("DELETE FROM organizations WHERE name = 'Outra Organização Teste'");
    $pdo->exec("DELETE FROM waste_types WHERE name LIKE 'Tipo Teste%'");
    $pdo->exec("DELETE FROM users WHERE email LIKE '%@teste.local' OR email LIKE 'deleted\\_%@teste.local'");
    $pdo->exec("DELETE FROM login_attempts");
}
cleanup();
$anon = new Client($base);
$admin = new Client($base); $gestor = new Client($base); $oper = new Client($base);

echo "== Autenticação\n";
[$s] = $anon->call('GET', '/organizations'); check('sem login => 401', $s === 401, (string)$s);
[$s] = $anon->login('admin@ecomanager.local', 'errada'); check('senha errada => 401', $s === 401, (string)$s);
[$s, $j] = $admin->login('admin@ecomanager.local'); check('login admin => 200', $s === 200 && $admin->csrf !== '');
$gestor->login('gestor@ecomanager.local'); $oper->login('operador@ecomanager.local');
[$s, $j] = $admin->call('GET', '/auth/me'); check('me retorna usuário', $s === 200 && $j['data']['user']['role'] === 'ADMINISTRADOR');
[$s] = $admin->call('POST', '/sectors', ['name' => 'x'], false); check('POST sem CSRF => 403', $s === 403, (string)$s);
[$s] = $admin->call('GET', '/rota-inexistente'); check('rota inexistente => 404', $s === 404);
[$s] = $admin->call('PATCH', '/auth/me'); check('método errado => 405', $s === 405, (string)$s);

echo "== Permissões\n";
[$s] = $oper->call('GET', '/users'); check('operador não lista usuários (403)', $s === 403);
[$s] = $gestor->call('GET', '/users'); check('gestor não lista usuários (403)', $s === 403);
[$s] = $admin->call('GET', '/users'); check('admin lista usuários', $s === 200);
[$s] = $oper->call('GET', '/reports/waste'); check('operador sem relatórios (403)', $s === 403);
[$s] = $gestor->call('GET', '/reports/waste'); check('gestor com relatórios', $s === 200);
[$s] = $oper->call('POST', '/sectors', ['organization_id' => 1, 'name' => 'Teste']); check('operador não cria setor (403)', $s === 403, (string)$s);
[$s] = $gestor->call('POST', '/organizations', ['name' => 'Org X']); check('gestor não cria organização (403)', $s === 403);

echo "== Escopo por organização\n";
[$s, $j] = $admin->call('POST', '/organizations', ['name' => 'Outra Organização Teste', 'legal_id' => '99.999.999/0001-99']);
check('admin cria organização', $s === 201, $j['message'] ?? '');
$org2 = $j['data']['id'] ?? 0;
[$s, $j] = $admin->call('POST', '/sectors', ['organization_id' => $org2, 'name' => 'Setor Oculto']);
$sec2 = $j['data']['id'] ?? 0;
[$s] = $gestor->call('GET', "/sectors/$sec2"); check('gestor não vê setor de outra org (404)', $s === 404, (string)$s);
[$s] = $gestor->call('GET', "/sectors?organization_id=$org2"); check('gestor filtrando outra org (403)', $s === 403, (string)$s);
[$s] = $gestor->call('POST', '/water-consumption', ['organization_id' => $org2, 'sector_id' => $sec2, 'reference_month' => '2026-01', 'quantity' => 1, 'unit' => 'm3']);
check('gestor não grava em outra org (403)', $s === 403, (string)$s);
[$s, $j] = $gestor->call('GET', '/organizations'); check('gestor vê só sua org', $s === 200 && count($j['data']) === 1, json_encode($j['meta'] ?? null));

echo "== Água / conversão / validação\n";
[$s, $j] = $gestor->call('GET', '/sectors'); $sectors = $j['data']; $sec = $sectors[0]['id']; $org = $sectors[0]['organization_id'];
[$s, $j] = $gestor->call('POST', '/water-consumption', ['organization_id' => $org, 'sector_id' => $sec, 'reference_month' => '2020-03', 'quantity' => 5000, 'unit' => 'L', 'notes' => '<script>x</script>']);
check('cria água em litros', $s === 201, json_encode($j)); $wid = $j['data']['id'] ?? 0;
[$s, $j] = $gestor->call('GET', "/water-consumption/$wid"); check('converteu 5000 L => 5 m³', (float)$j['data']['quantity_m3'] === 5.0, json_encode($j['data'] ?? null));
[$s, $j] = $gestor->call('POST', '/water-consumption', ['organization_id' => $org, 'sector_id' => $sec, 'reference_month' => '2020-13', 'quantity' => -1, 'unit' => 'xx']);
check('validação => 422 com erros', $s === 422 && count($j['errors']) >= 3, (string)$s);
[$s, $j] = $gestor->call('POST', '/water-consumption', ['organization_id' => $org, 'sector_id' => $sec2, 'reference_month' => '2020-03', 'quantity' => 1, 'unit' => 'm3']);
check('setor de outra org => 422', $s === 422, (string)$s);
[$s, $j] = $oper->call('POST', '/water-consumption', ['organization_id' => $org, 'sector_id' => $sec, 'reference_month' => '2020-04', 'quantity' => 2, 'unit' => 'm3']);
check('operador cria água', $s === 201); $owid = $j['data']['id'] ?? 0;
[$s] = $oper->call('PUT', "/water-consumption/$wid", ['quantity' => 9, 'unit' => 'm3']); check('operador não edita registro alheio (403)', $s === 403, (string)$s);
[$s] = $oper->call('PUT', "/water-consumption/$owid", ['quantity' => 3, 'unit' => 'm3']); check('operador edita o próprio', $s === 200, (string)$s);
[$s] = $oper->call('DELETE', "/water-consumption/$owid"); check('operador não exclui (403)', $s === 403);
[$s] = $gestor->call('DELETE', "/water-consumption/$owid"); check('gestor exclui (204)', $s === 204, (string)$s);
[$s] = $gestor->call('GET', "/water-consumption/$owid"); check('excluído => 404', $s === 404);
[$s, $j] = $gestor->call('GET', '/water-consumption?per_page=5&sort=-quantity_m3'); 
check('paginação + ordenação', $s === 200 && count($j['data']) === 5 && $j['meta']['per_page'] === 5 && (float)$j['data'][0]['quantity_m3'] >= (float)$j['data'][1]['quantity_m3']);
[$s, $j] = $gestor->call('GET', '/water-consumption?sort=id%3BDROP%20TABLE%20users'); check('sort malicioso é ignorado', $s === 200);
[$s, $j] = $gestor->call('GET', "/water-consumption?sector_id=1%20OR%201=1"); check('SQLi em filtro inofensivo', $s === 200);

echo "== Energia: comparação\n";
[$s, $j] = $gestor->call('POST', '/energy-consumption', ['organization_id' => $org, 'sector_id' => $sec, 'reference_month' => '2019-01', 'quantity_kwh' => 100]);
$e1 = $j['data']['id']; 
$gestor->call('POST', '/energy-consumption', ['organization_id' => $org, 'sector_id' => $sec, 'reference_month' => '2019-02', 'quantity_kwh' => 120]);
[$s, $j] = $gestor->call('GET', '/energy-consumption/compare?a_from=2019-01&a_to=2019-01&b_from=2019-02&b_to=2019-02');
check('variação 100 => 120 = +20%', $s === 200 && $j['data']['variation_pct'] === 20.0, json_encode($j['data'] ?? $j));
[$s, $j] = $gestor->call('GET', '/energy-consumption/compare?a_from=2010-01&a_to=2010-01&b_from=2019-02&b_to=2019-02');
check('base zero => variação nula (sem divisão por zero)', $s === 200 && $j['data']['variation_pct'] === null);

echo "== Resíduos\n";
[$s, $j] = $gestor->call('GET', '/waste-destinations'); $dest = array_column($j['data'], 'id', 'code');
[$s, $j] = $gestor->call('GET', '/waste-types'); $wt = $j['data'][0]['id'];
$wasteIds = [];
foreach ([['t', 1, 'RECICLAGEM'], ['kg', 500, 'ATERRO']] as [$u, $q, $d]) {
    [$s, $j] = $gestor->call('POST', '/waste', ['organization_id' => $org, 'sector_id' => $sec, 'waste_type_id' => $wt, 'destination_id' => $dest[$d], 'record_date' => '2018-05-10', 'quantity' => $q, 'unit' => $u]);
    check("cria resíduo $q$u $d", $s === 201, json_encode($j)); $wasteIds[] = $j['data']['id'] ?? 0;
}
[$s, $j] = $gestor->call('GET', '/waste/summary?from=2018-05-01&to=2018-05-31');
check('total 1500 kg, reciclado 1000, 66,67%', $j['data']['total_kg'] == 1500 && $j['data']['recycled_kg'] == 1000 && $j['data']['recycling_pct'] == 66.67, json_encode($j['data'] ?? null));
[$s, $j] = $gestor->call('GET', '/waste/summary?from=1999-01-01&to=1999-12-31');
check('sem dados => taxa nula, total 0', $j['data']['total_kg'] == 0 && $j['data']['recycling_pct'] === null && $j['data']['has_data'] === false);
[$s] = $gestor->call('POST', '/waste-types', ['category_id' => 1, 'name' => 'Tipo Teste']); check('gestor cria tipo de resíduo', $s === 201);
[$s] = $oper->call('POST', '/waste-types', ['category_id' => 1, 'name' => 'Tipo Teste 2']); check('operador não cria tipo (403)', $s === 403);

echo "== Metas\n";
[$s, $j] = $gestor->call('GET', '/environmental-objectives'); $obj = $j['data'][0]['id'];
$goal = ['organization_id' => $org, 'objective_id' => $obj, 'title' => 'Meta teste', 'metric' => 'ENERGY_KWH', 'direction' => 'DECREASE', 'baseline_value' => 100, 'target_value' => 150, 'start_date' => '2019-01-01', 'due_date' => '2019-12-31'];
[$s, $j] = $gestor->call('POST', '/environmental-goals', $goal); check('redução com alvo > base => 422', $s === 422, (string)$s);
$goal['target_value'] = 50;
[$s, $j] = $gestor->call('POST', '/environmental-goals', $goal); check('cria meta de redução', $s === 201, json_encode($j)); $gid = $j['data']['id'] ?? 0;
// base 100 -> alvo 50; último mês com dados (2019-02) = 120 (acima da base) => progresso negativo, clamp 0
[$s, $j] = $gestor->call('GET', "/environmental-goals/$gid");
check('progresso fora da direção = 0% e raw negativo', $j['data']['progress_pct'] === 0.0 && $j['data']['progress_raw_pct'] === -40.0 && $j['data']['status'] === 'ATRASADA', json_encode($j['data'] ?? $j));
$gestor->call('POST', '/energy-consumption', ['organization_id' => $org, 'sector_id' => $sec, 'reference_month' => '2019-03', 'quantity_kwh' => 75]);
[$s, $j] = $gestor->call('GET', "/environmental-goals/$gid");
check('redução 100->50, atual 75 => 50%', $j['data']['progress_pct'] === 50.0, json_encode($j['data']['progress_pct'] ?? null));
$gestor->call('POST', '/energy-consumption', ['organization_id' => $org, 'sector_id' => $sec, 'reference_month' => '2019-04', 'quantity_kwh' => 40]);
[$s, $j] = $gestor->call('GET', "/environmental-goals/$gid");
check('atual 40 => 100% ATINGIDA (clamp)', $j['data']['progress_pct'] === 100.0 && $j['data']['status'] === 'ATINGIDA', json_encode($j['data'] ?? null));
[$s, $j] = $gestor->call('GET', "/environmental-goals/$gid/progress"); check('histórico de progresso', $s === 200 && count($j['data']['history']) === 4, json_encode($j['data']['history'] ?? null));
$inc = $goal; $inc['direction'] = 'INCREASE'; $inc['metric'] = 'RECYCLING_RATE'; $inc['baseline_value'] = 20; $inc['target_value'] = 40; $inc['start_date'] = '2018-01-01'; $inc['due_date'] = '2018-12-31';
[$s, $j] = $gestor->call('POST', '/environmental-goals', $inc); $gid2 = $j['data']['id'] ?? 0;
[$s, $j] = $gestor->call('GET', "/environmental-goals/$gid2");
check('aumento 20->40, atual 66,67% => ATINGIDA', $j['data']['status'] === 'ATINGIDA' && $j['data']['current_value'] == 66.67, json_encode($j['data'] ?? null));
[$s] = $oper->call('POST', '/environmental-goals', $goal); check('operador não cria meta (403)', $s === 403);

echo "== Indicadores / dashboard / relatórios\n";
[$s, $j] = $gestor->call('GET', '/indicators/water'); check('indicador de água', $s === 200 && $j['data']['has_data'] && count($j['data']['history']) >= 12);
[$s, $j] = $gestor->call('GET', '/indicators/goals'); check('indicador de metas', $s === 200 && $j['data']['total'] >= 4);
[$s, $j] = $gestor->call('GET', '/dashboard/summary'); check('dashboard summary', $s === 200 && isset($j['data']['recycling']));
[$s, $j] = $gestor->call('GET', '/dashboard/charts'); check('dashboard charts', $s === 200 && isset($j['data']['goals_evolution']));
[$s, $j] = $gestor->call('GET', '/dashboard/summary?from=2000-13'); check('período inválido => 422', $s === 422, (string)$s);
[$s, $j, $raw] = $gestor->call('GET', '/reports/waste?format=csv'); check('CSV de resíduos', $s === 200 && str_contains($raw, 'Quantidade (kg)'));

echo "== Usuários\n";
[$s, $j] = $admin->call('POST', '/users', ['name' => 'Novo Usuário', 'email' => 'novo@teste.local', 'password' => '12345678', 'role' => 'OPERADOR', 'organization_ids' => [$org]]);
check('admin cria usuário', $s === 201, json_encode($j)); $uid = $j['data']['id'] ?? 0;
[$s] = $admin->call('POST', '/users', ['name' => 'Dup', 'email' => 'novo@teste.local', 'password' => '12345678', 'role' => 'OPERADOR']); check('e-mail duplicado => 422', $s === 422);
[$s] = $admin->call('POST', '/users', ['name' => 'Curta', 'email' => 'c@teste.local', 'password' => '123', 'role' => 'OPERADOR']); check('senha curta => 422', $s === 422);
[$s, $j] = $admin->call('GET', "/users/$uid"); check('resposta não expõe hash', !isset($j['data']['password_hash']) && !str_contains(json_encode($j), 'password'));
$n = new Client($base); [$s] = $n->login('novo@teste.local', '12345678'); check('novo usuário loga', $s === 200);
[$s] = $admin->call('DELETE', "/users/$uid"); check('admin exclui usuário', $s === 204);
[$s] = $n->call('GET', '/organizations'); check('sessão do excluído invalidada (401)', $s === 401, (string)$s);
[$s] = $admin->call('DELETE', '/users/' . $j['data']['id'] ?? 0); 
[$s, $me] = $admin->call('GET', '/auth/me'); [$s] = $admin->call('DELETE', '/users/' . $me['data']['user']['id']); check('admin não exclui a si mesmo', $s === 422, (string)$s);

echo "== Bloqueio por tentativas\n";
$bf = new Client($base); $codes = [];
for ($i = 0; $i < 7; $i++) { [$s] = $bf->login('operador@ecomanager.local', 'errada'); $codes[] = $s; }
check('429 após excesso de tentativas', in_array(429, $codes, true), implode(',', $codes));

echo "== Limpeza\n";
cleanup();
echo "\nResultado: $pass ok, $fail falhas\n";
exit($fail ? 1 : 0);
