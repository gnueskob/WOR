<?php

namespace lsb\App;

use lsb\Libs\Router;
use lsb\App\controller\User;

class WOR extends Router {

    public function __construct()
    {
        parent::__construct();
        $router = $this;

        $router->use('/user', new User());
    }
}