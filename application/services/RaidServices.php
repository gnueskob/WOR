<?php

namespace lsb\App\services;

use Exception;
use lsb\App\models\RaidDAO;
use lsb\App\query\RaidQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;
use PDOStatement;

class RaidServices
{
    /**
     * @param PDOStatement $stmt
     * @return RaidDAO
     * @throws Exception
     */
    private static function getRaidDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new RaidDAO($res);
    }

    /************************************************************/

    /**
     * @param int $raidId
     * @return RaidDAO
     * @throws CtxException|Exception
     */
    public static function getRaid(int $raidId)
    {
        $dao = new RaidDAO();
        $dao->raidId = $raidId;

        $stmt = RaidQuery::qSelectRaid($dao)->run();
        $raid = static::getRaidDAO($stmt);
        CtxException::invalidRaid($raid->isEmpty());
        return $raid;
    }

    /**
     * @param int $userId
     * @return RaidDAO
     * @throws Exception
     */
    public static function getRaidByUser(int $userId)
    {
        $dao = new RaidDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = RaidQuery::qSelectRaidByUser($dao)->run();
        return static::getRaidDAO($stmt);
    }

    /**
     * @param int $userId
     * @throws CtxException|Exception
     */
    public static function checkWarring(int $userId)
    {
        $dao = new RaidDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = RaidQuery::qSelectActiveRaidByUser($dao)->run();
        $war = static::getRaidDAO($stmt);
        CtxException::alreadyRaidExists(!$war->isEmpty());
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
        int $finishUnitTime)
    {
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
     * @param int $userId
     * @throws Exception
     */
    public static function removeWar(int $userId)
    {
        $dao = new WarDAO();
        $dao->userId = $userId;

        $stmt = WarQuery::qDeleteWar($dao)->run();
        static::validateDelete($stmt);
    }

    /**
     * @param int $userId
     * @return RaidDAO
     * @throws Exception
     */
    public static function refreshRaid(int $userId)
    {
        $dao = new RaidDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = RaidQuery::qSelcetFinishedRaidByUser($dao)->run();
        return static::getRaidDAO($stmt);

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
     * @param RaidDAO $raid
     * @throws CtxException
     */
    public static function resolveRaidResult(RaidDAO $raid)
    {
        if (false === $raid->isVictory) {
            // 레이드 실패

        }
    }
}