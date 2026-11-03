<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Operação realizada com sucesso.', int $status = 200, array $meta = []): never
    {
        $out = ['success' => true, 'message' => $message, 'data' => $data];
        if ($meta) {
            $out['meta'] = $meta;
        }
        self::json($out, $status);
    }

    public static function created(mixed $data, string $message = 'Registro cadastrado com sucesso.'): never
    {
        self::success($data, $message, 201);
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    public static function error(string $message, int $status = 400, array $errors = []): never
    {
        self::json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}
