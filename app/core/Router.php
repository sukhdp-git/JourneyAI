<?php
declare(strict_types=1);

namespace App\Core;

/** Minimal router: static and {param} segments, GET/POST, per-route middleware. */
final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable|array $handler, array $mw = []): void
    {
        $this->add('GET', $pattern, $handler, $mw);
    }

    public function post(string $pattern, callable|array $handler, array $mw = []): void
    {
        $this->add('POST', $pattern, $handler, $mw);
    }

    public function add(string $method, string $pattern, callable|array $handler, array $mw = []): void
    {
        $regex = rtrim($pattern, '/') ?: '/';
        $regex = str_replace(['{id}', '{n}'], ['(?P<id>[0-9]+)', '(?P<n>[0-9]+)'], $regex);
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[A-Za-z0-9\-_.]+)', $regex) . '$#';
        $this->routes[] = compact('method', 'pattern', 'regex', 'handler', 'mw');
    }

    public function dispatch(Request $req): void
    {
        $allowed = [];
        foreach ($this->routes as $r) {
            if (!preg_match($r['regex'], $req->path, $m)) {
                continue;
            }
            if ($r['method'] !== $req->method) {
                $allowed[] = $r['method'];
                continue;
            }
            $req->params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            foreach ($r['mw'] as $mw) {
                $mw($req);
            }
            $h = $r['handler'];
            if (is_array($h)) {
                [$class, $action] = $h;
                (new $class())->$action($req);
            } else {
                $h($req);
            }
            return;
        }
        Response::abort($allowed ? 405 : 404);
    }
}
