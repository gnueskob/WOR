<?php

namespace lsb\Libs;

/**
 * ErrorHandler class
*/
class EH
{
    public static function setError(int $code, string $msg): void
    {
        Context::getInstance()->setHeader($code, $msg);
    }

    public static function unauthenticatedHandler(): void
    {
        self::setError(401, "Unauthenticated");
    }

    public static function defaultRequestHandler(): void
    {
        self::setError(404, "Not Found");
    }

    public static function invalidMethodHandler(): void
    {
        self::setError(405, "Method Not Allowed");
    }
}
