<?php
declare(strict_types=1);

namespace App\Core;

class HttpException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $status = 400, public readonly array $errors = [])
    {
        parent::__construct($message);
    }
}

final class ValidationException extends HttpException
{
    public function __construct(array $errors, string $message = 'Dados inválidos.')
    {
        parent::__construct($message, 422, $errors);
    }
}

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Recurso não encontrado.')
    {
        parent::__construct($message, 404);
    }
}

final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Você não tem permissão para esta ação.')
    {
        parent::__construct($message, 403);
    }
}

final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Autenticação necessária.')
    {
        parent::__construct($message, 401);
    }
}
