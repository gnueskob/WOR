<?php

namespace lsb\App;

use lsb\Libs\Router;

class App extends Router
{
    public function group(string $group, Router $router)
    {
        foreach ($this->httpMethods as $value) {
            $method = strtolower($value);
            if (empty($router->{$method})) {
                continue;
            }

            $appMethods = empty($this->{$method}) ? array() : $this->{$method};

            $routerMethods = array();
            foreach ($router->{$method} as $route => $callback) {
                $groupByRoute = rtrim($group . $route, '/');
                $routerMethods[$groupByRoute] = $callback;
            }

            $this->{$method} = array_merge($appMethods, $routerMethods);
        }
    }
}
