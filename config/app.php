<?php
declare(strict_types=1);

use App\Core\Env;

return [
    'name'  => Env::get('APP_NAME', 'EcoManager'),
    'env'   => Env::get('APP_ENV', 'production'),
    'debug' => filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'session_lifetime' => (int) Env::get('SESSION_LIFETIME', '7200'),
    'timezone' => 'America/Sao_Paulo',
    'login_max_attempts' => 5,
    'login_lockout_minutes' => 10,
];
