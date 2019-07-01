<?php

namespace lsb\Libs;

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

    public function checkAllowedMethod(array $httpMethods): void
    {
        $method = $this->req->requestMethod;
        if (!in_array(strtoupper($method), $httpMethods)) {
            $this->res->error($this->req->serverProtocol, 405, "Method Not Allowed");
        }
    }

    /**
     * Run all of middleware appended to this context
    */
    public function runMiddlewares(): void
    {
        if (count($this->middlewares) === 0) {
            $this->res->error($this->req->serverProtocol, 404, "Not Found");
        }

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

    public function getReqBody()
    {
        if (is_null($this->data)) {
            $this->data = array_merge($this->req->getParams(), $this->req->body);
        }
        return $this->data;
    }

    public function addResBody(array $data): void
    {
        if (is_null($this->res->body)) {
            $this->res->body = [];
        }
        $this->res->body = array_merge($this->res->body, $data);
    }
}
