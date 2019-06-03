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

    /**************************************
     ** Only Logic Exceptions            **
     **************************************/
    /* @throws  CtxException */
    public function invaildUser(): void
    {
        $this->serverErrCode = 1000;
        $this->serverMsg = 'invalid user';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function registerFail(): void
    {
        $this->serverErrCode = 1001;
        $this->serverMsg = 'register failed';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function alreadyRegistered(): void
    {
        $this->serverErrCode = 1002;
        $this->serverMsg = 'user is already registered';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function alreadyUsedName(): void
    {
        $this->serverErrCode = 1003;
        $this->serverMsg = 'input name is already in use';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function invalidId(): void
    {
        $this->serverErrCode = 1004;
        $this->serverMsg = 'input id is not valid';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function invalidHiveId(): void
    {
        $this->serverErrCode = 1005;
        $this->serverMsg = 'input hive id is not valid';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function insertBufFail(): void
    {
        $this->serverErrCode = 1006;
        $this->serverMsg = 'input buf record failed';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function insertBuildingFail(): void
    {
        $this->serverErrCode = 1007;
        $this->serverMsg = 'input building record failed';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function invalidBuildingId(): void
    {
        $this->serverErrCode = 1008;
        $this->serverMsg = 'input building id is not valid';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function invalidBuildingUpgrade(): void
    {
        $this->serverErrCode = 1009;
        $this->serverMsg = 'building upgrade logic is not valid';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function insertBuildingUpgradeFail(): void
    {
        $this->serverErrCode = 1010;
        $this->serverMsg = 'building upgrade is fail';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function updateBuildingUpgradeFail(): void
    {
        $this->serverErrCode = 1011;
        $this->serverMsg = 'updating building upgrade is fail';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function deleteBuildingUpgradeFail(): void
    {
        $this->serverErrCode = 1012;
        $this->serverMsg = 'deleting building upgrade is fail';
        $this->throwLogicException();
    }

    /**************************************
     ** Fatal Exceptions                 **
     **************************************/
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
    public function throwNotFoundException(): void
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
