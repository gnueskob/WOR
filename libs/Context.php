<?php

namespace lsb\Libs;

use lsb\Libs\CtxException AS CE;

class Context extends Singleton implements IContext
{
    public $req;
    public $res;
    private $data = null;
    private $middlewares;

    protected function __construct()
    {
        parent::__construct();
        $this->req = new Request();
        $this->res = new Response();
        $this->middlewares = [];
    }

    /**
     * @param array $httpMethods
     * @throws CtxException
     */
    public function checkAllowedMethod(array $httpMethods): void
    {
        $method = $this->req->requestMethod;
        CE::invalidMethodException(!in_array(strtoupper($method), $httpMethods), ErrorCode::HTTP_ERROR);
    }

    /**
     * Run all of middleware appended to this context
     * @throws  CtxException
    */
    public function runMiddlewares(): void
    {
        CE::notFoundException(count($this->middlewares) === 0, ErrorCode::HTTP_ERROR);

        // Use to reduce next() function using array_pop()
        $this->middlewares = array_reverse($this->middlewares);
        $this->next();
    }

    /**
     * Pop one of middleware appended to this context And run
     * If there is no middleware anymore, return
    */
    public function next(): void
    {
        if (count($this->middlewares) === 0) {
            return;
        }

        // array_pop takes O(1)
        $middleware = array_pop($this->middlewares);

        call_user_func_array($middleware, [$this]);
        return;
    }

    /**
     * Add each middleware to this context
     * @param   callable    $middleware
    */
    public function addMiddleware(callable $middleware): void
    {
        array_push($this->middlewares, $middleware);
    }

    public function send(): void
    {
        $this->res->send();
    }

    public function getBody()
    {
        if (is_null($this->data)) {
            $this->data = array_merge($this->req->getParams(), $this->req->body);
        }
        return $this->data;
    }

    public function addBody(array $data): void
    {
        $this->res->body = array_merge($this->res->body, $data);
    }
}
