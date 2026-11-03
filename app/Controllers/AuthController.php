<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;
use App\Validators\Validator;

final class AuthController extends BaseController
{
    public function login(Request $r): void
    {
        $d = Validator::validate($r->body(), ['email' => 'required|email', 'password' => 'required|string|max:200']);
        $res = (new AuthService())->login($r, $d['email'], $d['password']);
        Response::success($res, 'Login realizado com sucesso.');
    }

    public function logout(Request $r): void
    {
        (new AuthService())->logout($r);
        Response::success(null, 'Sessão encerrada.');
    }

    public function me(Request $r): void
    {
        Session::start();
        Response::success(['user' => $r->user, 'csrf_token' => Session::csrfToken()]);
    }

    /** Preparado para futura integração com envio de e-mail. */
    public function forgotPassword(Request $r): void
    {
        Response::error('Recuperação de senha ainda não disponível. Solicite a redefinição a um administrador.', 501);
    }
}
