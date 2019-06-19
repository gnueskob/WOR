<?php

namespace lsb\App\services;

use Exception;
use lsb\App\models\BossDAO;
use lsb\App\models\RaidDAO;
use lsb\App\query\RaidQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;
use PDOStatement;

class RaidServices extends Services
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

    /**
     * @param PDOStatement $stmt
     * @return BossDAO
     * @throws Exception
     */
    private static function getBossDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new BossDAO($res);
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
     * @param int $territoryId
     * @return BossDAO
     * @throws CtxException|Exception
     */
    public static function checkBossGen(int $territoryId)
    {
        $dao = new BossDAO();
        $dao->territoryId = $territoryId;

        $stmt = RaidQuery::qSelectBossByTerritory($dao)->run();
        $boss = static::getBossDAO($stmt);
        CtxException::notGeneratedBoss($boss->isEmpty());
        return $boss;
    }

    /**
     * @param BossDAO $boss
     * @param int $userId
     * @param int $territoryId
     * @param int $finishUnitTime
     * @return int
     * @throws CtxException
     */
    public static function createRaid(BossDAO $boss, int $userId, int $territoryId, int $finishUnitTime)
    {
        $dao = new RaidDAO();
        $dao->raidId = null;
        $dao->bossId = $boss->bossId;
        $dao->userId = $userId;
        $dao->territoryId = $territoryId;
        $dao->bossType = $boss->bossType;
        $dao->isVictory = null;
        $dao->finishTime = Timezone::getCompleteTime($finishUnitTime);

        $stmt = RaidQuery::qInsertRaid($dao)
            ->checkError([DUPLICATE_ERRORCODE])
            ->run();
        $err = static::validateInsert($stmt);
        CtxException::alreadyRaidExists($err === DUPLICATE_ERRORCODE);
        return DB::getLastInsertId();
    }

    /**
     * @param BossDAO $boss
     * @param int $userId
     * @param int $attack
     * @param bool $pending
     * @throws CtxException
     */
    public static function attackBoss(BossDAO $boss, int $userId, int $attack, bool $pending = false)
    {
        $dao = new BossDAO();
        $dao->bossId = $boss->bossId;
        $dao->hitPoint = $attack;

        // 첫 보스 공격 유저이면 전투 시작
        if (is_null($boss->userId)) {
            list($activeUnitTime) = Plan::getBossUnitTime($boss->bossType);
            $dao->finishTime = Timezone::getCompleteTime($activeUnitTime);
            $dao->userId = $userId;

            $query = RaidQuery::qSubtractBossHP($dao);
            static::validateUpdate($query, true);

            $query = RaidQuery::qStartBossAttack($dao);
            static::validateUpdate($query, $pending);
        } else {
            if ($boss->hitPoint < $attack) {
                // 보스 사망시 승리 처리
                $daoRaid = new RaidDAO();
                $daoRaid->bossId = $boss->bossId;
                $query = RaidQuery::qSetVictory($daoRaid);
                static::validateUpdate($query, false);

                $stmt = RaidQuery::qDeleteBoss($boss)->run();
                static::validateDelete($stmt);
            } else {
                $query = RaidQuery::qSubtractBossHP($dao);
                static::validateUpdate($query, $pending);
            }
        }
    }

    /**
     * @param int $raidId
     * @throws CtxException
     */
    public static function removeRaid(int $raidId)
    {
        $dao = new RaidDAO();
        $dao->raidId = $raidId;

        $stmt = RaidQuery::qDeleteRaid($dao)->run();
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
     * @param BossDAO $boss
     * @param int $finishUnitTime
     * @throws CtxException
     */
    public static function checkTooLate(BossDAO $boss, int $finishUnitTime)
    {
        $finishTime = Timezone::getCompleteTime($finishUnitTime);
        CtxException::raidTooLate(isset($boss->finishTime) && $finishTime > $boss->finishTime);
    }

    /**
     * @param RaidDAO $raid
     * @throws CtxException
     */
    public static function checkFinished(RaidDAO $raid)
    {
        CtxException::notFinishedYet(empty($raid->isVictory));
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
            return;
        }

        list($buffType) = Plan::getBossBuff($raid->bossType);
        $finishUnitTime = Plan::getBuffFinishUnitTime($buffType);

        BuffServices::createBuff($raid->userId, $buffType, $finishUnitTime);

        list($trophyType) = Plan::getBossTrophy($raid->bossType);
        list($tactical, $food, $luxury) = Plan::getTrophyRewardResources($trophyType);

        UserServices::obtainResource($raid->userId, $tactical, $food, $luxury);
    }
}
