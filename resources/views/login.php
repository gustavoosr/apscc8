<?php use App\Core\View; ?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Entrar · EcoManager</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="login-wrap">
  <section class="login-hero">
    <div class="brand"><i class="fa-solid fa-leaf"></i> EcoManager</div>
    <div>
      <h2>Transforme dados ambientais em decisões.</h2>
      <ul>
        <li><i class="fa-solid fa-circle-check"></i>Consumo de água e energia por setor</li>
        <li><i class="fa-solid fa-circle-check"></i>Geração e destinação de resíduos</li>
        <li><i class="fa-solid fa-circle-check"></i>Metas ambientais com acompanhamento de progresso</li>
      </ul>
    </div>
    <small class="opacity-75">Sistema web de monitoramento e gestão de indicadores ambientais. ISO 14001 como referência conceitual.</small>
  </section>
  <section class="login-form-side">
    <form class="login-card" id="loginForm" novalidate>
      <h1 class="h3 fw-bold mb-1">Bem-vindo</h1>
      <p class="text-muted mb-4">Acesse com seu e-mail e senha.</p>
      <div class="alert alert-danger d-none" id="loginError" role="alert"></div>
      <div class="mb-3"><label class="form-label" for="email">E-mail</label>
        <div class="input-group"><span class="input-group-text"><i class="fa-regular fa-envelope"></i></span><input class="form-control" id="email" type="email" autocomplete="username" required autofocus></div></div>
      <div class="mb-3"><label class="form-label" for="password">Senha</label>
        <div class="input-group"><span class="input-group-text"><i class="fa-solid fa-lock"></i></span><input class="form-control" id="password" type="password" autocomplete="current-password" required></div></div>
      <button class="btn btn-primary w-100 py-2" id="loginBtn" type="submit">Entrar</button>
      <p class="text-center mt-3 mb-0"><a href="#" id="forgot" class="small">Esqueci minha senha</a></p>
    </form>
  </section>
</div>
<script src="/assets/js/api.js"></script>
<script>
const form = document.getElementById('loginForm'), err = document.getElementById('loginError'), btn = document.getElementById('loginBtn');
form.addEventListener('submit', async (e) => {
  e.preventDefault(); err.classList.add('d-none'); btn.disabled = true;
  try {
    await Api.post('/auth/login', { email: document.getElementById('email').value, password: document.getElementById('password').value });
    location.href = '/dashboard';
  } catch (x) {
    err.textContent = (x.errors && x.errors[0] && x.errors[0].message) ? 'Verifique os campos: ' + x.errors[0].message : x.message;
    err.classList.remove('d-none'); btn.disabled = false;
  }
});
document.getElementById('forgot').addEventListener('click', async (e) => {
  e.preventDefault();
  try { await Api.post('/auth/password/forgot', {}); } catch (x) { err.textContent = x.message; err.classList.remove('d-none'); }
});
</script>
</body>
</html>
