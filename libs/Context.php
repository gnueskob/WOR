<?php

namespace lsb\Libs;

use lsb\Utils\Dev;
use Exception;

class Context extends Singleton
{
    public $err;
    public $req;
    public $res;
    public $middlewares;

    private $middlewareCnt = 0;

    protected function __construct()
    {
        parent::__construct();
        $this->req = new Request();
        $this->res = new Response();
        $this->middlewares = [];
    }

    public function runMiddlewares()
    {
        // Use to reduce next() function using array_pop()
        $this->middlewares = array_reverse($this->middlewares);
        $this->next();
    }

    public function next(): void
    {
        if (count($this->middlewares) === 0) {
            return;
        }

        // array_pop takes O(1)
        $middleware = array_pop($this->middlewares);
        $this->middlewareCnt++;
        try {
            call_user_func_array($middleware, [$this]);
        } catch (Exception $e) {
            Dev::log($e);
        }
        return;
    }

    public function setHeader(int $code, string $msg): void
    {
        $protocol = $this->req->serverProtocol;
        $this->res->setHeader($protocol, $code, $msg);
    }

    public function addMiddleware(array $middleware): void
    {
        array_push($this->middlewares, ...$middleware);
    }

    public function doesRequestHandlerExist(): bool
    {
        return $this->middlewareCnt >= 0;
    }
}
