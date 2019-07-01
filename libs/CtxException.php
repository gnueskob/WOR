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
     * @param int $errorCode
     * @throws CtxException
     */
    public static function dbException(int $errorCode): void
    {
        throw new CtxException($errorCode, 250, "Internal Server Error");
    }
}
