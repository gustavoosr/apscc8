<?php
use App\Core\View;
$e = fn ($v) => View::e($v);
$role = $user['role'];
$isManager = in_array($role, ['ADMINISTRADOR', 'GESTOR'], true);
$nav = [
    ['Visão geral', null, null],
    ['Dashboard', '/dashboard', 'fa-gauge-high'],
    ['Monitoramento', null, null],
    ['Consumo de água', '/water', 'fa-droplet'],
    ['Consumo de energia', '/energy', 'fa-bolt'],
    ['Resíduos', '/waste', 'fa-recycle'],
    ['Gestão', null, null],
    ['Objetivos e metas', '/goals', 'fa-bullseye'],
    ['Organizações', '/organizations', 'fa-building'],
    ['Setores', '/sectors', 'fa-sitemap'],
];
if ($isManager) { $nav[] = ['Tipos de resíduos', '/waste-types', 'fa-dumpster']; $nav[] = ['Relatórios', '/reports', 'fa-file-lines']; }
if ($role === 'ADMINISTRADOR') { $nav[] = ['Administração', null, null]; $nav[] = ['Usuários', '/users', 'fa-users-gear']; }
$roleLabel = ['ADMINISTRADOR' => 'Administrador', 'GESTOR' => 'Gestor', 'OPERADOR' => 'Operador'][$role];
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $e($title) ?> · EcoManager</title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Ctext y='20' font-size='20'%3E🌿%3C/text%3E%3C/svg%3E">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<aside class="sidebar" id="sidebar">
  <a class="brand" href="/dashboard"><i class="fa-solid fa-leaf"></i> EcoManager</a>
  <nav class="nav flex-column">
    <?php foreach ($nav as [$label, $href, $icon]): ?>
      <?php if ($href === null): ?><div class="nav-section"><?= $e($label) ?></div>
      <?php else: ?><a class="nav-link <?= $path === $href ? 'active' : '' ?>" href="<?= $e($href) ?>"><i class="fa-solid <?= $e($icon) ?>"></i><?= $e($label) ?></a><?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">Gestão de indicadores ambientais<br>Referência conceitual: ISO 14001</div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main">
  <header class="topbar">
    <button class="btn btn-light d-lg-none" id="menuToggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <h1><?= $e($title) ?></h1>
    <div class="ms-auto user-chip">
      <div class="meta"><div class="fw-semibold"><?= $e($user['name']) ?></div><small><?= $e($roleLabel) ?></small></div>
      <div class="avatar"><?= $e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></div>
      <button class="btn btn-outline-secondary btn-sm" id="logoutBtn" title="Sair"><i class="fa-solid fa-right-from-bracket"></i></button>
    </div>
  </header>
  <main class="content"><?= $content ?></main>
  <footer class="disclaimer">EcoManager é uma ferramenta de apoio ao monitoramento ambiental. A família ISO 14000 (em especial a ISO 14001) é utilizada apenas como referência conceitual; o sistema não certifica nem atesta conformidade com a norma.</footer>
</div>
<script>window.APP = { csrf: <?= json_encode($csrf) ?>, role: <?= json_encode($role) ?>, userId: <?= (int) $user['id'] ?> };</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/ui.js"></script>
<script src="/assets/js/crud.js"></script>
<script src="/assets/js/pages.js"></script>
<?= $scripts ?? '' ?>
</body>
</html>
