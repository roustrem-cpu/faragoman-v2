<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareInterface;

/**
 * Convention-light router with named routes, parameter capture and a
 * per-route middleware pipeline. Routing logic is fully separated from
 * controllers (Separation of Concerns) and lives in routes/web.php.
 */
final class Router
{
    /** @var array<int, array{method:string,pattern:string,handler:array,middleware:array<int,string>}> */
    private array $routes = [];

    public function __construct(private Container $container)
    {
    }

    /**
     * @param array{0:class-string,1:string} $handler
     * @param array<int,string>              $middleware
     */
    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    /**
     * @param array{0:class-string,1:string} $handler
     * @param array<int,string>              $middleware
     */
    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    /**
     * @param array{0:class-string,1:string} $handler
     * @param array<int,string>              $middleware
     */
    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler', 'middleware');
    }

    public function dispatch(Request $request): Response
    {
        $path = $request->path();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            return $this->runPipeline($route, $request, $params);
        }

        return $this->notFound();
    }

    /**
     * @return array<string, string>|null
     */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param array{handler:array,middleware:array<int,string>} $route
     * @param array<string,string>                              $params
     */
    private function runPipeline(array $route, Request $request, array $params): Response
    {
        $core = function (Request $request) use ($route, $params): Response {
            [$class, $method] = $route['handler'];
            $controller = $this->container->get($class);

            return $controller->{$method}($request, ...array_values($params));
        };

        // Compose middleware in reverse so the first listed runs first.
        foreach (array_reverse($route['middleware']) as $name) {
            /** @var MiddlewareInterface $middleware */
            $middleware = $this->container->get($name);
            $next = $core;
            $core = static fn (Request $r): Response => $middleware->handle($r, $next);
        }

        return $core($request);
    }

    private function notFound(): Response
    {
        /** @var View $view */
        $view = $this->container->get(View::class);

        return Response::html($view->render('errors.404'), 404);
    }
}
