<?php

use lsb\Libs\Router;
use lsb\Libs\Context;

$planRouter = new Router();

$planRouter->get('/', function (Context $ctx) {
    $ctx->res->body = "Plan";
    $ctx->res->send();
});
