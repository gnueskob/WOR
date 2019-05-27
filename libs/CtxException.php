<?php

namespace lsb\Libs;

use Exception;

class CtxException extends Exception
{
    private $serverErrCode;
    private $serverMsg;

    public function __construct(
        int $serverErrCode = 1,
        string $serverMsg = '',
        string $message = '',
        $code = 404,
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->serverErrCode = $serverErrCode;
        $this->serverMsg = $serverMsg;
    }

    public function getServerErrCode(): int
    {
        return $this->serverErrCode;
    }

    public function getServerMsg(): string
    {
        return $this->serverMsg;
    }

    /**
     * @param   int     $code
     * @param   string  $msg
     * @throws  CtxException
    */
    private function throwException(int $code, string $msg): void
    {
        $this->serverErrCode = $code;
        $this->serverMsg = $msg;
        $this->code = $code;
        $this->message = $msg;
        throw $this;
    }

    /* @throws  CtxException */
    public function throwUnauthenticatedException(): void
    {
        $this->throwException(401, "Unauthenticated");
    }

    /* @throws  CtxException */
    public function throwMethodNotAllowedException(): void
    {
        $this->throwException(404, "Not Found");
    }

    /* @throws  CtxException */
    public function throwInvalidMethodException(): void
    {
        $this->throwException(405, "Method Not Allowed");
    }

    /* @throws  CtxException */
    public function throwInternalServerException(): void
    {
        $this->throwException(500, "Internal Server Error");
    }
}
