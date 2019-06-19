<?php

namespace lsb\App\services;

use lsb\App\models\BuildingDAO;
use lsb\App\models\WarDAO;
use lsb\App\query\WarQuery;
use Exception;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;
use PDOException;
use PDOStatement;

class WarServices extends Services
{
    /**
     * @param PDOStatement $stmt
     * @return WarDAO
     * @throws Exception
     */
    private static function getWarDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new WarDAO($res);
    }

    /************************************************************/

    /**
     * @param int $warId
     * @return WarDAO
     * @throws CtxException|Exception
     */
    public static function getWar(int $warId)
    {
        $dao = new WarDAO();
        $dao->warId = $warId;

        $stmt = WarQuery::qSelectWar($dao)->run();
        $war = static::getWarDAO($stmt);
        CtxException::invalidWar($war->isEmpty());
        return $war;
    }

    /**
     * @param int $userId
     * @return WarDAO
     * @throws Exception
     */
    public static function getWarByUser(int $userId)
    {
        $dao = new WarDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = WarQuery::qSelectWarByUser($dao)->run();
        return static::getWarDAO($stmt);
    }


        /**
     * @param int $userId
     * @return WarDAO
     * @throws Exception
     */
    public static function checkWarring(int $userId)
    {
        $dao = new WarDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = WarQuery::qSelectActiveWarByUser($dao)->run();
        $war = static::getWarDAO($stmt);
        CtxException::alreadyWarExists(!$war->isEmpty());
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @param int $attack
     * @param int $friendAttack
     * @param int $manpower
     * @param int $foodResource
     * @param int $targetDefense
     * @param int $prepareUnitTime
     * @param int $finishUnitTime
     * @return int
     * @throws CtxException
     */
    public static function createWar(
        int $userId,
        int $territoryId,
        int $attack,
        int $friendAttack,
        int $manpower,
        int $foodResource,
        int $targetDefense,
        int $prepareUnitTime,
        int $finishUnitTime
    ) {
        $dao = new WarDAO();
        $dao->warId = null;
        $dao->userId = $userId;
        $dao->territoryId = $territoryId;
        $dao->attack = $attack;
        $dao->friendAttack = $friendAttack;
        $dao->manpower = $manpower;
        $dao->foodResource = $foodResource;
        $dao->targetDefense = $targetDefense;
        $dao->prepareTime = Timezone::getCompleteTime($prepareUnitTime);
        $dao->finishTime = Timezone::getCompleteTime($finishUnitTime);

        $stmt = WarQuery::qInsertWar($dao)
            ->checkError([DUPLICATE_ERRORCODE])
            ->run();
        $err = static::validateInsert($stmt);
        CtxException::alreadyWarExists($err === DUPLICATE_ERRORCODE);
        return DB::getLastInsertId();
    }

    /**
     * @param int $warId
     * @throws CtxException
     */
    public static function removeWar(int $warId)
    {
        $dao = new WarDAO();
        $dao->warId = $warId;

        $stmt = WarQuery::qDeleteWar($dao)->run();
        static::validateDelete($stmt);
    }

    /**
     * @param int $userId
     * @return WarDAO|void
     * @throws Exception
     */
    public static function refreshWar(int $userId)
    {
        $dao = new WarDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = WarQuery::qSelcetFinishedWarByUser($dao)->run();
        return static::getWarDAO($stmt);
    }

    /**********************************************************/

    // CHECK

    /**
     * @param WarDAO $war
     * @throws CtxException|Exception
     */
    public static function checkFinished(WarDAO $war)
    {
        CtxException::notFinishedYet(!$war->isFinished());
    }

    /**
     * @param WarDAO $war
     * @throws CtxException|Exception
     */
    public static function checkPrepared(WarDAO $war)
    {
        CtxException::alreadyWarPrepared($war->isPrepared());
    }

    /**********************************************************/

    /**
     * @param WarDAO $war
     * @throws CtxException
     */
    public static function resolveWarResult(WarDAO $war)
    {
        $attack = $war->attack;
        $defense = $war->targetDefense;
        $ratio = $attack - $defense / $attack;

        if ($ratio <= 0) { // 패배, 무승부
            return;
        }

        $manpower = $war->manpower;
        $food = $war->foodResource;
        list(, , , $defaultManpowerRatio) = Plan::getUnitWar();

        $manpower -= (int)($manpower * $defaultManpowerRatio);
        $remainManpower = $manpower * $ratio;
        $remainFood = $food * $ratio;

        UserServices::obtainManpower($war->userId, $remainManpower, true);
        UserServices::obtainResource($war->userId, 0, $remainFood, 0);
    }
}
