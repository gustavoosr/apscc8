<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:mixed, middleware:array}> */
    private array $routes = [];
    private string $prefix = '';
    private array $groupMiddleware = [];

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $prevP = $this->prefix;
        $prevM = $this->groupMiddleware;
        $this->prefix .= $prefix;
        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);
        $callback($this);
        $this->prefix = $prevP;
        $this->groupMiddleware = $prevM;
    }

    public function add(string $method, string $path, array|callable $handler, array $middleware = []): void
    {
        $full = rtrim($this->prefix . $path, '/') ?: '/';
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $full) . '$#';
        $this->routes[] = [
            'method' => $method,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function get(string $p, array|callable $h, array $m = []): void { $this->add('GET', $p, $h, $m); }
    public function post(string $p, array|callable $h, array $m = []): void { $this->add('POST', $p, $h, $m); }
    public function put(string $p, array|callable $h, array $m = []): void { $this->add('PUT', $p, $h, $m); }
    public function patch(string $p, array|callable $h, array $m = []): void { $this->add('PATCH', $p, $h, $m); }
    public function delete(string $p, array|callable $h, array $m = []): void { $this->add('DELETE', $p, $h, $m); }

    /** Registra GET/POST/PUT/PATCH/DELETE padrão para um controller CRUD. */
    public function resource(string $path, string $controller, array $middleware = [], array $writeMiddleware = []): void
    {
        $w = array_merge($middleware, $writeMiddleware);
        $this->get($path, [$controller, 'index'], $middleware);
        $this->get($path . '/{id}', [$controller, 'show'], $middleware);
        $this->post($path, [$controller, 'store'], $w);
        $this->put($path . '/{id}', [$controller, 'update'], $w);
        $this->patch($path . '/{id}', [$controller, 'update'], $w);
        $this->delete($path . '/{id}', [$controller, 'destroy'], $w);
    }

    public function dispatch(Request $request): void
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $m)) {
                continue;
            }
            if ($route['method'] !== $request->method) {
                $allowed[] = $route['method'];
                continue;
            }
            $request->params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->run($route, $request);
            return;
        }
        if ($allowed) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            Response::error('Método não permitido.', 405);
        }
        throw new NotFoundException('Rota não encontrada.');
    }

    private function run(array $route, Request $request): void
    {
        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            fn (callable $next, string $mw) => fn (Request $r) => $this->callMiddleware($mw, $r, $next),
            function (Request $r) use ($route) {
                $h = $route['handler'];
                if (is_array($h)) {
                    return (new $h[0]())->{$h[1]}($r);
                }
                return $h($r);
            }
        );
        $pipeline($request);
    }

    private function callMiddleware(string $spec, Request $r, callable $next): mixed
    {
        // Formato "Classe" ou "Classe:arg1,arg2"
        [$class, $args] = array_pad(explode(':', $spec, 2), 2, '');
        $args = $args === '' ? [] : explode(',', $args);
        return (new $class())->handle($r, $next, ...$args);
    }
}
