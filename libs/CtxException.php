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
     ** DB Logical Exceptions            **
     **************************************/
    /**
     * @param string $tag
     * @throws CtxException
     */
    public function selectFail(string $tag): void
    {
        $this->serverErrCode = 10000;
        $this->serverMsg = "select fail :{$tag}";
        $this->throwDBLogicException();
    }

    /**
     * @param string $tag
     * @throws CtxException
     */
    public function insertFail(string $tag): void
    {
        $this->serverErrCode = 10001;
        $this->serverMsg = "insert fail :{$tag}";
        $this->throwDBLogicException();
    }

    /**
     * @param string $tag
     * @throws CtxException
     */
    public function updateFail(string $tag): void
    {
        $this->serverErrCode = 10002;
        $this->serverMsg = "update fail :{$tag}";
        $this->throwDBLogicException();
    }

    /**
     * @param string $tag
     * @throws CtxException
     */
    public function deleteFail(string $tag): void
    {
        $this->serverErrCode = 10003;
        $this->serverMsg = "delete fail :{$tag}";
        $this->throwDBLogicException();
    }

    /**
     * @param string $tag
     * @throws CtxException
     */
    public function transactionFail(string $tag): void
    {
        $this->serverErrCode = 10004;
        $this->serverMsg = "transaction fail :{$tag}";
        $this->throwDBLogicException();
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
    public function alreadyUsedTerritory(): void
    {
        $this->serverErrCode = 1004;
        $this->serverMsg = 'input territory is already in use';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function resourceInsufficient(): void
    {
        $this->serverErrCode = 1005;
        $this->serverMsg = 'resource Insufficient fail';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function invalidBuildingType(): void
    {
        $this->serverErrCode = 1006;
        $this->serverMsg = 'invalid building type';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function notYetCreatedBuilding(): void
    {
        $this->serverErrCode = 1007;
        $this->serverMsg = 'building is not created yet';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function exceedManpowerBuilding(): void
    {
        $this->serverErrCode = 1008;
        $this->serverMsg = 'manpower what you deploy is greater than max value';
        $this->throwLogicException();
    }

    /* @throws  CtxException */
    public function manpowerInsufficient(): void
    {
        $this->serverErrCode = 1009;
        $this->serverMsg = 'manpower Insufficient fail';
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
    public function throwDBLogicException(): void
    {
        $this->throwException(201, "DB Logic error");
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
