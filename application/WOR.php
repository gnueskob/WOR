<?php

namespace lsb\App;

use lsb\App\controller\Alliance;
use lsb\App\controller\Buff;
use lsb\App\controller\Building;
use lsb\App\controller\Exploration;
use lsb\App\controller\Plan;
use lsb\App\controller\War;
use lsb\App\controller\Weapon;
use lsb\Config\utils\BodyParser;
use lsb\Config\utils\Error;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\App\controller\User;
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
            ->use('/user', new User())
            ->use('/building', new Building())
            ->use('/weapon', new Weapon())
            ->use('/exploration', new Exploration())
            ->use('/buff', new Buff())
            ->use('/alliance', new Alliance())
            ->use('/war', new War());
    }
}
