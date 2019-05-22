<?php

namespace lsb\Libs;

class Context extends Singleton implements IContext
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
            $this->err->throwInternalServerError();
        }
    }

    /**
     * Run all of middleware appended to this context
     * @throws  CtxException
    */
    public function runMiddlewares(): void
    {
        if (count($this->middlewares) === 0) {
            $this->err->throwDefaultRequestError();
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

    /**
     * Throw CtxException for some reason
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
}
