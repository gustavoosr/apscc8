<?php
declare(strict_types=1);

namespace App\Core;

final class ExceptionHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handle']);
        set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
            if (!(error_reporting() & $no)) {
                return false;
            }
            throw new \ErrorException($str, 0, $no, $file, $line);
        });
    }

    public static function handle(\Throwable $e): void
    {
        $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');

        if ($e instanceof HttpException) {
            if ($isApi) {
                Response::error($e->getMessage(), $e->status, $e->errors);
            }
            self::page($e->status, $e->getMessage());
        }

        Logger::error($e);
        $debug = (bool) Config::get('app.debug', false);
        $msg = $debug ? $e->getMessage() : 'Erro interno do servidor.';
        if ($isApi) {
            Response::error($msg, 500, $debug ? [['trace' => $e->getFile() . ':' . $e->getLine()]] : []);
        }
        self::page(500, $msg);
    }

    private static function page(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        $m = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo "<!doctype html><meta charset='utf-8'><title>Erro $status</title>"
           . "<link rel='stylesheet' href='/assets/css/app.css'>"
           . "<div class='error-page'><h1>$status</h1><p>$m</p><a href='/'>Voltar ao início</a></div>";
        exit;
    }
}
