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

    /**
     * @param int $errorCode
     * @param string $msg
     * @throws CtxException
     */
    private function throwException(int $errorCode, string $msg): void
    {
        $this->errorCode = $errorCode;
        $this->message = $msg;
        throw $this;
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

    /* @throws CtxException */
    public function throwUnauthenticatedException(): void
    {
        $this->throwException(401, "Unauthenticated");
    }

    /* @throws CtxException */
    public function throwNotFoundException(): void
    {
        $this->throwException(404, "Not Found");
    }

    /* @throws CtxException */
    public function throwInvalidMethodException(): void
    {
        $this->throwException(405, "Method Not Allowed");
    }

    /* @throws CtxException */
    public function throwInternalServerException(): void
    {
        $this->throwException(500, "Internal Server Error");
    }
}
