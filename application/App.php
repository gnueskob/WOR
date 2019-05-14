<?php

namespace lsb\App;

use lsb\Libs\Request;
use lsb\Libs\Router;

class App extends Router
{
    public function __construct()
    {
        parent::__construct(new Request());
    }
}
