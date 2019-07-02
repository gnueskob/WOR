<?php

namespace lsb\Config\utils;

use lsb\Config\Config;
use lsb\Libs\Context;
use lsb\Libs\Encrypt;

class BodyParser
{
    public static function encryptParser(): callable
    {
        return function (Context $ctx): void {
            $ctx->req->body = Encrypt::decrypt($ctx->req->body);
            try {
                $ctx->next();
            } finally {
                $ctx->res->body = Encrypt::encrypt($ctx->res->body);
            }
        };
    }

    public static function jsonParser(): callable
    {
        return function (Context $ctx): void {
            $ctx->req->body = json_decode($ctx->req->body, true);
            if (is_null($ctx->req->body)) {
                $ctx->req->body = [];
            }
            try {
                $ctx->next();
            } finally {
                $ctx->res->body = json_encode($ctx->res->body);
            }
        };
    }
}
