<?php

namespace lsb\Libs;

use lsb\Utils\DEV;
use Exception;

class Context extends Singleton
{
    public $err;
    public $req;
    public $res;
    public $middlewares;

    private $start = false;

    protected function __construct()
    {
        parent::__construct();
        $this->req = new Request();
        $this->res = new Response();
        $this->middlewares = array();
    }

    public function runMiddlewares()
    {
        if (!$this->start) {
            $this->start = true;
            if (count($this->middlewares) === 0) {
                // TODO: default req 핸들러 처리 how
                EH::defaultRequestHandler();
                return;
            }
        }
        $this->next();
    }

    public function next(): void
    {
        if (count($this->middlewares) === 0) {
            return;
        }
        $middleware = $this->middlewares[0];
        $this->middlewares = array_slice($this->middlewares, 1);

        try {
            call_user_func_array($middleware, array($this));
        } catch (Exception $e) {
            DEV::log($e);
        }
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
}
