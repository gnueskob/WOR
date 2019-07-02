<?php

namespace lsb\App;

use lsb\App\controller\Plan;
use lsb\Config\utils\BodyParser;
use lsb\Config\utils\Error;
use lsb\Libs\Context;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\App\controller\User;
use lsb\Utils\Auth;
use lsb\Utils\Logger;

class WOR extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // common middleware
        $router
            // ->use('/', BodyParser::encryptParser())
            ->use('/', BodyParser::jsonParser())
            ->use('/', Error::errorHandler())
            ->use('/', Logger::errorLogger())
            ->use('/', Logger::APILogger());

        $router
            ->use('/plan', new Plan())
            ->use('/user', new User());
    }
}
