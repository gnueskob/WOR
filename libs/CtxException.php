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
        int $code = 404,
        string $message = '',
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
     * @param int $code
     * @param string $msg
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
     * @param bool $flag
     * @throws CtxException
     */
    public static function selectFail(bool $flag = true): void
    {
        $serverErrCode = 10000;
        $serverMsg = "select fail";
        self::throwDBLogicException($serverErrCode, $serverMsg, $flag);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function insertFail(bool $flag = true): void
    {
        $serverErrCode = 10001;
        $serverMsg = "insert fail";
        self::throwDBLogicException($serverErrCode, $serverMsg, $flag);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function updateFail(bool $flag = true): void
    {
        $serverErrCode = 10002;
        $serverMsg = "update fail";
        self::throwDBLogicException($serverErrCode, $serverMsg, $flag);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function deleteFail(bool $flag = true): void
    {
        $serverErrCode = 10003;
        $serverMsg = "delete fail";
        self::throwDBLogicException($serverErrCode, $serverMsg, $flag);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function transactionFail(bool $flag = true): void
    {
        $serverErrCode = 10004;
        $serverMsg = "transaction fail";
        self::throwDBLogicException($serverErrCode, $serverMsg, $flag);
    }

    /**************************************
     ** Only Logic Exceptions            **
     **************************************/

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidUser(bool $flag = true): void
    {
        $serverErrCode = 1000;
        $msg = 'invalid user';
        self::throwLogicException($flag, $serverErrCode, $msg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidWeapon(bool $flag = true): void
    {
        $serverErrCode = 1000;
        $serverMsg = 'invalid weapon';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function registerFail(bool $flag = true): void
    {
        $serverErrCode = 1001;
        $serverMsg = 'register failed';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyRegistered(bool $flag = true): void
    {
        $serverErrCode = 1002;
        $serverMsg = 'user is already registered';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyUsedName(bool $flag = true): void
    {
        $serverErrCode = 1003;
        $serverMsg = 'input name is already in use';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyUsedTerritory(bool $flag = true): void
    {
        $serverErrCode = 1004;
        $serverMsg = 'input territory is already in use';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function resourceInsufficient(bool $flag = true): void
    {
        $serverErrCode = 1005;
        $serverMsg = 'resource Insufficient fail';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidBuildingType(bool $flag = true): void
    {
        $serverErrCode = 1006;
        $serverMsg = 'invalid building type';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notYetCreatedBuilding(bool $flag = true): void
    {
        $serverErrCode = 1007;
        $serverMsg = 'building is not created yet';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notFinishedYet(bool $flag = true): void
    {
        $serverErrCode = 1007;
        $serverMsg = 'war is not finished yet';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notUpgradable(bool $flag = true): void
    {
        $serverErrCode = 1007;
        $serverMsg = 'building is not upgradable';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }


    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function exceedManpowerBuilding(bool $flag = true): void
    {
        $serverErrCode = 1008;
        $serverMsg = 'manpower what you deploy is greater than max value';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function manpowerInsufficient(bool $flag = true): void
    {
        $serverErrCode = 1009;
        $serverMsg = 'manpower Insufficient fail';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notUsedTile(bool $flag = true): void
    {
        $serverErrCode = 1010;
        $serverMsg = 'tile is not available';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notUsedTerritory(bool $flag = true): void
    {
        $serverErrCode = 1011;
        $serverMsg = 'territory is not available';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notCreatedYet(bool $flag = true): void
    {
        $serverErrCode = 1012;
        $serverMsg = 'not created yet';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notUpgradedYet(bool $flag = true): void
    {
        $serverErrCode = 1012;
        $serverMsg = 'not upgraded yet';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notDeployedYet(bool $flag = true): void
    {
        $serverErrCode = 1012;
        $serverMsg = 'not deployed yet';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notCompletedPreviousJobYet(bool $flag = true): void
    {
        $serverErrCode = 1012;
        $serverMsg = 'job is not done yet';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function maxLevel(bool $flag = true): void
    {
        $serverErrCode = 1012;
        $serverMsg = 'current level is max';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyUsedTile(bool $flag = true): void
    {
        $serverErrCode = 1013;
        $serverMsg = 'tile is already used another building';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyExistsBuff(bool $flag = true): void
    {
        $serverErrCode = 1014;
        $serverMsg = 'buff is already in use';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyExplored(bool $flag = true): void
    {
        $serverErrCode = 1014;
        $serverMsg = 'already explored';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notExploredYet(bool $flag = true): void
    {
        $serverErrCode = 1015;
        $serverMsg = 'location is not explored yet';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notGeneratedBoss(bool $flag = true): void
    {
        $serverErrCode = 1015;
        $serverMsg = 'not generated boss';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidType(bool $flag = true): void
    {
        $serverErrCode = 1016;
        $serverMsg = 'type of body in request is invalid';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyWarExists(bool $flag = true): void
    {
        $serverErrCode = 1017;
        $serverMsg = 'war is already exists';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyRaidExists(bool $flag = true): void
    {
        $serverErrCode = 1017;
        $serverMsg = 'raid is already exists';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function raidTooLate(bool $flag = true): void
    {
        $serverErrCode = 1017;
        $serverMsg = 'can not prepare raid, too late';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyRequestedAlliance(bool $flag = true): void
    {
        $serverErrCode = 1017;
        $serverMsg = 'Alliance is already requested';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function alreadyWarPrepared(bool $flag = true): void
    {
        $serverErrCode = 1017;
        $serverMsg = 'war is already prepared';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidBuilding(bool $flag = true): void
    {
        $serverErrCode = 1018;
        $serverMsg = 'building id is invalid';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidBuff(bool $flag = true): void
    {
        $serverErrCode = 1018;
        $serverMsg = 'buff id is invalid';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidTile(bool $flag = true): void
    {
        $serverErrCode = 1018;
        $serverMsg = 'Tile id is invalid';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidWar(bool $flag = true): void
    {
        $serverErrCode = 1018;
        $serverMsg = 'War id is invalid';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidRaid(bool $flag = true): void
    {
        $serverErrCode = 1018;
        $serverMsg = 'Raid id is invalid';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidTerritory(bool $flag = true): void
    {
        $serverErrCode = 1018;
        $serverMsg = 'Territory id is invalid';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function invalidAlliance(bool $flag = true): void
    {
        $serverErrCode = 1018;
        $serverMsg = 'Alliance id is invalid';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notAlliance(bool $flag = true): void
    {
        $serverErrCode = 1018;
        $serverMsg = 'no alliance with friend id';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**
     * @param bool $flag
     * @throws CtxException
     */
    public static function notBossTerritory(bool $flag = true): void
    {
        $serverErrCode = 1018;
        $serverMsg = 'not Boss Territory';
        self::throwLogicException($flag, $serverErrCode, $serverMsg);
    }

    /**************************************
     ** Fatal Exceptions                 **
     **************************************/
    /**
     * @param int $serverCode
     * @param string $clientMsg
     * @param bool $flag
     * @throws CtxException
     */
    private static function throwLogicException(bool $flag, int $serverCode, string $clientMsg): void
    {
        if (false === $flag) {
            return;
        }
        $function = debug_backtrace()[2]['function'];
        throw new CtxException($serverCode, "Logic Error at {$function}", 250, $clientMsg);
    }

    /**
     * @param int $scode
     * @param string $qry
     * @param bool $flag
     * @throws CtxException
     */
    private static function throwDBLogicException(int $scode, string $qry, bool $flag): void
    {
        if (false === $flag) {
            return;
        }
        $function = debug_backtrace()[2]['function'];
        throw new CtxException($scode, "DB Login Error: {$qry} in {$function}", "DB Logic Error", 251);
    }

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
