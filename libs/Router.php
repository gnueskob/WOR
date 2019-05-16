<?php

namespace lsb\Libs;

class Router
{
    protected $ctx;
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
        if (!in_array(strtoupper($methodName), $this->httpMethods)) {
            $this->invalidMethodHandler();
            return;
        }

        $formatedRoute = $this->formatRoute($route);
        $this->{strtolower($methodName)}[$formatedRoute] = $middlewares;
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
     * Removes trailing forward slashes from the right of the route.
     * @param string route
     * @return string result
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
     * @param string route
     * @return string regexPattern
    */
    private function getRouteRegexPattern(string $route): string
    {
        $paramPattern = '[a-zA-Z0-9-_]+';
        $routePattern = preg_replace('/\//', '\\/', $route);
        $routePattern = preg_replace('/:[a-zA-Z0-9-_]+/', $paramPattern, $routePattern);
        $routePattern = '/^' . $routePattern . '$/';
        return $routePattern;
    }

    /**
     * Find parameters about request route URL
     * @param string $route     route already registered in Router
     * @param string $reqRoute  route where user send request
     * @return array $params    $params[:key] = $value
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
        $req = $this->ctx->req;

        $methodDictionary = $this->{strtolower($req->requestMethod)};
        $reqRoute = $this->formatRoute($req->requestUri);
        $middlewares = null;

        if (isset($methodDictionary[$reqRoute])) {
            $middlewares = $methodDictionary[$reqRoute];
        } else {
            // If there is no route exactly matched with key of methodDictionary
            // Find route with regex of each route which matches with req uri
            foreach ($methodDictionary as $route => $_) {
                if (preg_match($this->getRouteRegexPattern($route), $reqRoute)) {
                    // If find route matching current URL
                    // then, register the function what we applied in Controller
                    $middlewares = $methodDictionary[$route]['method'];

                    // And also register the parameters in URL to request object
                    $req->setParams($this->findRouteParams($route, $reqRoute));
                    break;
                }
            }
        }

        if (is_null($middlewares)) {
            $this->defaultRequestHandler();
            return;
        }

        $this->ctx->middlewares = $middlewares;
    }

    public function run()
    {
        $this->resolve();
    }

    private function setHeader(int $code, string $msg)
    {
        $res = $this->ctx->res;
        $protocol = $this->ctx->req->serverProtocol;
        $res->setHeader($protocol, $code, $msg);
    }

    public function invalidMethodHandler()
    {
        $this->setHeader(405, "Method Not Allowed");
    }

    public function defaultRequestHandler()
    {
        $this->setHeader(404, "Not Found");
    }

    public function unauthenticatedHandler()
    {
        $this->setHeader(401, "Unauthenticated");
    }
}
