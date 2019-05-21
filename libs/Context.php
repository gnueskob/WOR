<?php

namespace lsb\Libs;

class Context extends Singleton
{
    public $err;
    public $req;
    public $res;

    private $middlewares;

    protected function __construct()
    {
        parent::__construct();
        $this->err = new CtxException();
        $this->req = new Request();
        $this->res = new Response();
        $this->middlewares = [];
    }

    /**
     * Check current request method is allowed
     * @param   array   $httpMethods    allowed methods
     * @throws  CtxException
     */
    public function checkAllowedMethod(array $httpMethods): void
    {
        $method = $this->req->requestMethod;
        if (!in_array(strtoupper($method), $httpMethods)) {
            $this->err->internalServerErrorHandler();
        }
    }

    /**
     * Run all of middleware appended to this context
     * @throws  CtxException
    */
    public function runMiddlewares(): void
    {
        if (count($this->middlewares) === 0) {
            $this->err->defaultRequestHandler();
        }

        // Use to reduce next() function using array_pop()
        $this->middlewares = array_reverse($this->middlewares);
        $this->next();
    }

    public function next(): bool
    {
        if (count($this->middlewares) === 0) {
            return false;
        }

        // array_pop takes O(1)
        $middleware = array_pop($this->middlewares);

        return call_user_func_array($middleware, [$this]);
    }

    public function addMiddleware(array $middleware): void
    {
        array_push($this->middlewares, ...$middleware);
    }

    /**
     * @param   int     $status
     * @param   string  $msg
     * @param   bool    $expose
     * @throws  CtxException
    */
    public function throw(int $status, string $msg = '', bool $expose = true)
    {
        $this->err->msg = $msg === '' ? 'Error occurred' : $msg;
        $this->err->status = $status;
        $this->err->expose = $expose;
        throw $this->err;
    }

//    public function redirect(string $method, string $path)
//    {
//
//    }
}
