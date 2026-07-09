<?php

declare(strict_types=1);

namespace App\Core {

    /**
     * Regex-based router with GET/POST support, named routes with reverse URL
     * generation, path parameters ({slug}, {id}, ...) and per-route middleware.
     *
     * Middleware are callables run before the handler. A middleware that returns
     * (bool) false short-circuits dispatch — it is expected to have already sent
     * a response (e.g. a redirect) before doing so.
     */
    final class Router
    {
        private static ?Router $instance = null;

        /**
         * @var array<int, array{
         *   method:string,
         *   pattern:string,
         *   regex:string,
         *   params:array<int,string>,
         *   handler:mixed,
         *   name:?string,
         *   middleware:array<int,callable>
         * }>
         */
        private array $routes = [];

        /**
         * @var array<string, array{pattern:string}>
         */
        private array $named = [];

        /** @var callable|null */
        private $notFoundHandler = null;

        /** @var callable|null */
        private $methodNotAllowedHandler = null;

        public function __construct()
        {
            self::$instance = $this;
        }

        public static function getInstance(): Router
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Register a route.
         *
         * @param string               $method     GET or POST
         * @param string               $pattern    e.g. /yazi/{cat}/{slug}
         * @param mixed                $handler    [Controller::class, 'method'] or closure
         * @param string|null          $name       named route for reverse routing
         * @param array<int, callable> $middleware closures run before the handler
         */
        public function add(
            string $method,
            string $pattern,
            mixed $handler,
            ?string $name = null,
            array $middleware = []
        ): self {
            $method  = strtoupper($method);
            $params  = [];
            $pattern = '/' . trim($pattern, '/');

            if ($pattern === '/') {
                $regex = '#^/?$#';
            } else {
                $regex = preg_replace_callback(
                    '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
                    static function (array $m) use (&$params): string {
                        $params[] = $m[1];
                        $constraint = $m[2] ?? '[^/]+';

                        return '(' . $constraint . ')';
                    },
                    $pattern
                );
                $regex = '#^' . $regex . '/?$#';
            }

            $this->routes[] = [
                'method'     => $method,
                'pattern'    => $pattern,
                'regex'      => $regex,
                'params'     => $params,
                'handler'    => $handler,
                'name'       => $name,
                'middleware' => $middleware,
            ];

            if ($name !== null) {
                $this->named[$name] = ['pattern' => $pattern];
            }

            return $this;
        }

        /**
         * @param mixed                $handler
         * @param array<int, callable> $middleware
         */
        public function get(string $pattern, mixed $handler, ?string $name = null, array $middleware = []): self
        {
            return $this->add('GET', $pattern, $handler, $name, $middleware);
        }

        /**
         * @param mixed                $handler
         * @param array<int, callable> $middleware
         */
        public function post(string $pattern, mixed $handler, ?string $name = null, array $middleware = []): self
        {
            return $this->add('POST', $pattern, $handler, $name, $middleware);
        }

        public function setNotFoundHandler(callable $handler): void
        {
            $this->notFoundHandler = $handler;
        }

        public function setMethodNotAllowedHandler(callable $handler): void
        {
            $this->methodNotAllowedHandler = $handler;
        }

        /**
         * Reverse-generate a URL from a named route and its parameters.
         * Params not consumed by the pattern are appended as a query string.
         *
         * @param array<string, string|int> $params
         */
        public function url(string $name, array $params = []): string
        {
            if (!isset($this->named[$name])) {
                return '/';
            }

            $pattern = $this->named[$name]['pattern'];
            $used    = [];

            $path = preg_replace_callback(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::[^}]+)?\}/',
                static function (array $m) use ($params, &$used): string {
                    $key = $m[1];
                    if (array_key_exists($key, $params)) {
                        $used[$key] = true;

                        return rawurlencode((string) $params[$key]);
                    }

                    return $m[0];
                },
                $pattern
            );

            $query = array_diff_key($params, $used);
            if ($query !== []) {
                $path .= '?' . http_build_query($query);
            }

            return $path;
        }

        /**
         * Match the current request, run middleware, then invoke the handler.
         */
        public function dispatch(?string $method = null, ?string $uri = null): void
        {
            $method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            $uri    = $uri ?? ($_SERVER['REQUEST_URI'] ?? '/');

            $path = parse_url($uri, PHP_URL_PATH);
            $path = is_string($path) ? rawurldecode($path) : '/';
            if ($path !== '/') {
                $path = '/' . trim($path, '/');
            }

            $pathMatchedButMethodMismatch = false;

            foreach ($this->routes as $route) {
                if (preg_match($route['regex'], $path, $matches) !== 1) {
                    continue;
                }

                if ($route['method'] !== $method) {
                    $pathMatchedButMethodMismatch = true;
                    continue;
                }

                array_shift($matches);
                $params = [];
                foreach ($route['params'] as $index => $paramName) {
                    $params[$paramName] = $matches[$index] ?? null;
                }

                foreach ($route['middleware'] as $middleware) {
                    $result = $middleware($params);
                    if ($result === false) {
                        return;
                    }
                }

                $this->invoke($route['handler'], $params);

                return;
            }

            if ($pathMatchedButMethodMismatch) {
                $this->handleMethodNotAllowed();

                return;
            }

            $this->handleNotFound();
        }

        /**
         * @param mixed                $handler
         * @param array<string, mixed> $params
         */
        private function invoke(mixed $handler, array $params): void
        {
            $args = array_values($params);

            if (is_array($handler) && count($handler) === 2) {
                [$class, $method] = $handler;
                $instance = is_object($class) ? $class : new $class();
                $args = $this->coerceArgs([$instance, $method], $args);
                $instance->{$method}(...$args);

                return;
            }

            if (is_callable($handler)) {
                $args = $this->coerceArgs($handler, $args);
                $handler(...$args);

                return;
            }

            $this->handleNotFound();
        }

        /**
         * Coerce positional route parameters (always captured as strings) to the
         * scalar types declared by the target callable. Handlers routinely
         * type-hint int (e.g. edit(int $id)); because this router file runs under
         * declare(strict_types=1), the dynamic call would otherwise raise a
         * TypeError for a string argument. Untyped, string, nullable-only or
         * non-scalar parameters are passed through unchanged.
         *
         * @param callable         $callable
         * @param array<int,mixed> $args
         * @return array<int,mixed>
         */
        private function coerceArgs(callable $callable, array $args): array
        {
            try {
                $ref = is_array($callable)
                    ? new \ReflectionMethod($callable[0], $callable[1])
                    : new \ReflectionFunction(\Closure::fromCallable($callable));
            } catch (\ReflectionException) {
                return $args;
            }

            $params = $ref->getParameters();

            foreach ($args as $i => $value) {
                if (!isset($params[$i]) || !is_string($value)) {
                    continue;
                }

                $type = $params[$i]->getType();
                if (!$type instanceof \ReflectionNamedType || !$type->isBuiltin()) {
                    continue;
                }

                switch ($type->getName()) {
                    case 'int':
                        if (preg_match('/^-?\d+$/', $value) === 1) {
                            $args[$i] = (int) $value;
                        }
                        break;
                    case 'float':
                        if (is_numeric($value)) {
                            $args[$i] = (float) $value;
                        }
                        break;
                    case 'bool':
                        $args[$i] = $value !== '' && $value !== '0' && strtolower($value) !== 'false';
                        break;
                }
            }

            return $args;
        }

        private function handleNotFound(): void
        {
            http_response_code(404);
            if ($this->notFoundHandler !== null) {
                ($this->notFoundHandler)();

                return;
            }

            header('Content-Type: text/html; charset=utf-8');
            echo '<h1>404 Not Found</h1>';
        }

        private function handleMethodNotAllowed(): void
        {
            http_response_code(405);
            if ($this->methodNotAllowedHandler !== null) {
                ($this->methodNotAllowedHandler)();

                return;
            }

            header('Content-Type: text/html; charset=utf-8');
            echo '<h1>405 Method Not Allowed</h1>';
        }
    }
}

namespace {

    if (!function_exists('route')) {
        /**
         * Global reverse-routing helper, backed by the active Router instance.
         *
         * @param array<string, string|int> $params
         */
        function route(string $name, array $params = []): string
        {
            return \App\Core\Router::getInstance()->url($name, $params);
        }
    }
}
