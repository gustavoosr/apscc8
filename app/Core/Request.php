<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public array $params = [];
    public ?array $user = null;
    private ?array $json = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        private readonly string $rawBody,
        private readonly array $headers
    ) {}

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $path = '/' . trim($path, '/');
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = $v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        return new self($method, $path, $_GET, file_get_contents('php://input') ?: '', $headers);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path, '/api/');
    }

    /** Corpo JSON decodificado (array vazio se ausente). */
    public function body(): array
    {
        if ($this->json === null) {
            $this->json = [];
            if (trim($this->rawBody) !== '') {
                $data = json_decode($this->rawBody, true);
                if (!is_array($data)) {
                    throw new HttpException('JSON inválido no corpo da requisição.', 400);
                }
                $this->json = $data;
            }
        }
        return $this->json;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body()[$key] ?? $default;
    }

    public function q(string $key, mixed $default = null): mixed
    {
        $v = $this->query[$key] ?? $default;
        return ($v === '' ) ? $default : $v;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
