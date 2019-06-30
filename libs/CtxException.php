<?php

namespace lsb\Libs;

use Exception;

class CtxException extends Exception
{
    public $errorCode;

    public function __construct(
        int $errorCode = 0,
        int $code = 200,
        string $message = 'Logic error',
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
    }

    /**************************************
     ** Only Logic Exceptions            **
     **************************************/

    /**
     * @param bool $flag
     * @param int $errorCode
     * @throws CtxException
     */
    public static function check(bool $flag, int $errorCode)
    {
        if (false === $flag) {
            return;
        }
        throw new CtxException($errorCode, 250, "Logic Error {$errorCode}");
    }

    /**************************************
     ** Fatal Exceptions                 **
     **************************************/

    /**
     * @param bool $flag
     * @param int $errorCode
     * @throws CtxException
     */
    public static function unauthenticatedException(bool $flag, int $errorCode): void
    {
        if (false === $flag) {
            return;
        }
        throw new CtxException($errorCode, 401, "Unauthenticated");
    }

    /**
     * @param bool $flag
     * @param int $errorCode
     * @throws CtxException
     */
    public static function notFoundException(bool $flag, int $errorCode): void
    {
        if (false === $flag) {
            return;
        }
        throw new CtxException($errorCode, 404, "Not Found");
    }

    /**
     * @param bool $flag
     * @param int $errorCode
     * @throws CtxException
     */
    public static function invalidMethodException(bool $flag, int $errorCode): void
    {
        if (false === $flag) {
            return;
        }
        throw new CtxException($errorCode, 405, "Method Not Allowed");
    }

    /**
     * @param bool $flag
     * @param int $errorCode
     * @throws CtxException
     */
    public static function internalServerException(bool $flag, int $errorCode): void
    {
        if (false === $flag) {
            return;
        }
        throw new CtxException($errorCode, 500, "Internal Server Error");
    }
}
