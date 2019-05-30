<?php

namespace lsb\Libs;

use Exception;

class CtxException extends Exception
{
    private $serverErrCode;
    private $serverMsg;

    public function __construct(
        int $serverErrCode = 0,
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
    public function throwInvaildUserException(): void
    {
        $this->serverErrCode = 1000;
        $this->serverMsg = 'invalid user';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function throwRegisterException(): void
    {
        $this->serverErrCode = 1001;
        $this->serverMsg = 'register failed';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function throwAlreadyRegisteredException(): void
    {
        $this->serverErrCode = 1002;
        $this->serverMsg = 'user is already registered';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function throwLogicException(): void
    {
        $this->throwException(200, "Logic error");
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
