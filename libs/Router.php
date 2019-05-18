<?php

namespace lsb\Libs;

class Router
{
    private $ctx;
    private $group = '';

    protected $httpMethods = array(
        "GET",
        "POST",
        "PUT"
    );

    public function __construct()
    {
        $this->ctx = Context::getInstance();
    }

    private function request(string $methodName, string $route, array $middlewares): void
    {
        if ($methodName !== 'all' && !in_array(strtoupper($methodName), $this->httpMethods)) {
            EH::invalidMethodHandler();
            return;
        }

        $formatedRoute = $this->formatRoute($route);
        $appliedMWs = &$this->{strtolower($methodName)}[$formatedRoute];
        if (is_null($appliedMWs)) {
            $appliedMWs = array();
        }
        array_push($appliedMWs, ...$middlewares);
    }

    public function get(string $path, ...$middlewares): void
    {
        $this->request('get', $path, $middlewares);
    }

    public function post(string $path, ...$middlewares): void
    {
        $this->request('post', $path, $middlewares);
    }

    public function put(string $path, ...$middlewares): void
    {
        $this->request('put', $path, $middlewares);
    }

    /**
     * Add middleware or router
     * @param string $path path to add router or middleware
     * @param object[] $middlewares middleware to be added (can contain router)
     * @return  void
     */
    public function use(string $path = '/', object ...$middlewares): void
    {
        $path = rtrim('/', $path);
        $path = $path === '' ? '/' : $path;

        foreach ($middlewares as $middleware) {
            if ($middleware instanceof Router) {
                $group = $this->group . $path;
                $middleware->group = $group === '/' ? '' : $group;
            }
        }
        $this->request('all', $path, $middlewares);
        return;
    }

    /**
     * Removes trailing forward slashes from the right of the route.
     * @param string  route
     * @return  string  result
     */
    private function formatRoute(string $route): string
    {
        $result = rtrim($route, '/');
        if ($result === '') {
            return '/';
        }
        return $result;
    }

    /**
     * Find regex pattern about route string
     * @param string $route route string
     * @param bool $end flag notifying end with this route string
     * @return  string  $regexPattern
     */
    private function getRouteRegexPattern(string $route, bool $end = false): string
    {
        $prefix = '/^';
        $postfix = $end ? '$/' : '/';
        $paramPattern = '[a-zA-Z0-9-_]+';
        $routePattern = preg_replace('/\//', '\\/', $route);
        $routePattern = preg_replace('/:[a-zA-Z0-9-_]+/', $paramPattern, $routePattern);
        $routePattern = $prefix . $routePattern . $postfix;
        return $routePattern;
    }

    /**
     * Find parameters about request route URL
     * @param string $route route already registered in Router
     * @param string $reqRoute route where user send request
     * @return  array   $params     $params[:key] = $value
     */
    private function findRouteParams(string $route, string $reqRoute): array
    {
        $paramKey = explode('/', ltrim($route, '/'));
        $paramValue = explode('/', ltrim($reqRoute, '/'));
        $params = array();
        foreach ($paramKey as $idx => $key) {
            if (preg_match('/^:.*/', $key)) {
                $params[ltrim($key, ':')] = $paramValue[$idx];
            }
        }
        return $params;
    }

    /**
     * Resolves a route
     */
    private function resolve(): void
    {
        $reqRoute = $this->formatRoute($this->ctx->req->requestUri);

        $allMethod = 'all';
        if (property_exists($this, $allMethod)) {
            $methodDictionary = $this->{$allMethod} ?: array();
            foreach ($methodDictionary as $path => $_) {
                $route = $this->group . $path;
                $prefixRegexPattern = $this->getRouteRegexPattern($route);
                if (preg_match($prefixRegexPattern, $reqRoute)) {
                    $middlewares = $methodDictionary[$route];
                    $this->ctx->addMiddleware($middlewares);
                    $this->ctx->req->setParams($this->findRouteParams($route, $reqRoute));
                }
            }
        }

        $method = strtolower($this->ctx->req->requestMethod);
        if (property_exists($this, $method)) {
            $methodDictionary = $this->{$method} ?: array();
            if (isset($methodDictionary[$reqRoute])) {
                $middlewares = $methodDictionary[$reqRoute];
                $this->ctx->addMiddleware($middlewares);
            } else {
                foreach ($methodDictionary as $path => $_) {
                    $route = $this->group . $path;
                    if (preg_match($this->getRouteRegexPattern($route, true), $reqRoute)) {
                        $middlewares = $methodDictionary[$route];
                        $this->ctx->addMiddleware($middlewares);
                        $this->ctx->req->setParams($this->findRouteParams($route, $reqRoute));
                    }
                }
            }
        }

        $this->ctx->runMiddlewares();
    }

    public function run(): void
    {
        $this->resolve();
    }
}
