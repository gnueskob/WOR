<?php

namespace lsb\libs;

class Context extends Singleton
{
    public $req;
    public $res;
    public $middlewares;

    protected function __construct()
    {
        parent::__construct();
        $this->req = new Request();
        $this->res = new Response();
    }

    public function next(): void
    {
        return;
    }
}
