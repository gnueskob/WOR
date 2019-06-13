<?php

namespace lsb\Libs;

use Exception;

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
     * Check current request method is allowed
     * @param   array   $httpMethods    allowed methods
     * @throws  CtxException
     */
    public function checkAllowedMethod(array $httpMethods): void
    {
        $method = $this->req->requestMethod;
        if (!in_array(strtoupper($method), $httpMethods)) {
            (new CtxException())->throwInvalidMethodException();
        }
    }

    /**
     * Run all of middleware appended to this context
     * @throws  CtxException
    */
    public function runMiddlewares(): void
    {
        if (count($this->middlewares) === 0) {
            (new CtxException())->throwNotFoundException();
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

    /**
     * Throw CtxException for some reason
     * @param   int         $serverErrCode
     * @param   string      $serverMsg
     * @param   string      $message
     * @param   int         $code
     * @throws  CtxException
     */
    public function throw(
        int $serverErrCode = 1,
        string $serverMsg = '',
        string $message = '',
        $code = 404
    ) {
        $message = $message === '' ? 'Error occurred' : $message;
        throw new CtxException($serverErrCode, $serverMsg, $message, $code, null);
    }
}
