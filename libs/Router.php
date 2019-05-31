<?php

namespace lsb\Libs;

use Exception;
use PDOException;

class Router
{
    private $ctx;
    private $group = '';

    protected $httpMethods = [
        "GET",
        "POST",
        "PUT"
    ];

    public function __construct()
    {
        $this->ctx = Context::getInstance();
    }

    private function request(string $methodName, string $route, array $middlewares): void
    {
        $isAllMethod = $methodName === 'all';
        $isReqMethod = $methodName === strtolower($this->ctx->req->requestMethod);
        if (!$isAllMethod && !$isReqMethod) {
            return;
        }

        $reqUri = $this->ctx->req->requestUri;
        $fullPath = $this->formatRoute($this->group . $route);
        $regexPattern = $this->getRouteRegexPattern($fullPath, !$isAllMethod);
        if (!preg_match($regexPattern, $reqUri)) {
            return;
        }

        $this->appendMiddleware($fullPath, $middlewares);
        $this->ctx->req->setParams($this->findRouteParams($fullPath, $reqUri));
    }

    public function get(string $path, ...$middlewares): Router
    {
        $this->request('get', $path, $middlewares);
        return $this;
    }

    public function post(string $path, ...$middlewares): Router
    {
        $this->request('post', $path, $middlewares);
        return $this;
    }

    public function put(string $path, ...$middlewares): Router
    {
        $this->request('put', $path, $middlewares);
        return $this;
    }

    /**
     * Add middleware or router
     * @param string $path path to add router or middleware
     * @param object[] $middlewares middleware Or Router to be added this path
     * @return  Router
     */
    public function use(string $path, object ...$middlewares): Router
    {
        $this->request('all', $path, $middlewares);
        return $this;
    }

    /**
     * Removes trailing forward slashes from the right of the route.
     * @param   string  $route
     * @param   bool    $setRoot
     * @return  string  $result
     */
    private function formatRoute(string $route, bool $setRoot = false): string
    {
        $result = rtrim($route, '/');
        if ($result === '' && $setRoot) {
            return '/';
        }
        return $result;
    }

    /**
     * Find regex pattern about route string
     * @param string $route route string
     * @param bool $end flag notifying end with this route string
     * @return  string      $regexPattern
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
     * @return  array       $params     $params[:key] = $value
     */
    private function findRouteParams(string $route, string $reqRoute): array
    {
        $paramKey = explode('/', ltrim($route, '/'));
        $paramValue = explode('/', ltrim($reqRoute, '/'));
        $params = [];
        foreach ($paramKey as $idx => $key) {
            if (preg_match('/^:.*/', $key)) {
                $params[ltrim($key, ':')] = $paramValue[$idx];
            }
        }
        return $params;
    }

    /**
     * Append middleware to current context $ctx
     * @param string $path
     * @param array $middlewares
     * @return  void
     */
    private function appendMiddleware(string $path, array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof ISubRouter) {
                // $group = $this->formatRoute($this->group . $path);
                $middleware->group = $path;
                $middleware->make();
                // $middleware->resolve();
            } else {
                $this->ctx->addMiddleware($middleware);
            }
        }
    }

    /**
     * Find all of middleware that appended to request route
     * and run them until there is no next()
     * If there exists some CtxException about middleware,
     * Error handling will be processed in this try/catch
     */
    public function run(): void
    {
        try {
            $this->ctx->checkAllowedMethod($this->httpMethods);
            $this->ctx->runMiddlewares();
        } catch (CtxException $e) {
            $res = $this->ctx->res;
            $req = $this->ctx->req;
            $res->error($req->serverProtocol, $e->getCode(), $e->getMessage());
            Log::getInstance()->addExceptionLog(CATEGORY_CTX_EX, $e);
        } catch (Exception $e) {
            // Internal Server Error
            Log::getInstance()->addExceptionLog(CATEGORY_EX, $e);
        } finally {
            Log::getInstance()->flushLog();
        }
    }
}
