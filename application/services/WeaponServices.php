<?php

namespace lsb\App\services;

use lsb\App\models\WeaponDAO;
use lsb\App\query\UserQuery;
use lsb\App\query\WeaponQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use PDOException;

class WeaponServices
{
    /**
     * @param int $weaponId
     * @return WeaponDAO
     * @throws CtxException|Exception
     */
    public static function getWeapon(int $weaponId)
    {
        $container = new WeaponDAO();
        $container->weaponId = $weaponId;

        $stmt = WeaponQuery::selectWeapon($container);
        $res = $stmt->fetch();
        if ($res === false) {
            return null;
        }
        return new WeaponDAO($res);
    }

    /**
     * @param int $userId
     * @return array|bool
     * @throws CtxException|Exception
     */
    public static function getWeaponsByUser(int $userId)
    {
        $container = new WeaponDAO();
        $container->userId = $userId;

        $stmt = WeaponQuery::selectWeaponsByUser($container);
        $res = $stmt->fetchAll();
        if ($res === false) {
            return [];
        }
        foreach ($res as $key => $value) {
            $res[$key] = new WeaponDAO($value);
        }
        return $res;
    }

    /**
     * @param int $userId
     * @param int $weaponType
     * @param string $creatTime
     * @return string
     * @throws CtxException|Exception
     */
    public static function createWeapon(int $userId, int $weaponType, string $creatTime)
    {
        $container = new WeaponDAO();
        $container->userId = $userId;
        $container->weaponType = $weaponType;
        $container->createTime = $creatTime;

        $stmt = WeaponQuery::insertWeapon($container);
        if ($stmt->rowCount() === 0) {
            (new CtxException())->insertFail();
        }
        $db = DB::getInstance()->getDBConnection();
        return $db->lastInsertId();
    }

    /*
    public static function resolveCreateBuilding(array $data)
    {
        // 기존 건물 건설 job 정보 검색
        $stmt = WeaponQuery::selectWeapon($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        $createFinishTime = $res['create_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 무기 제작 job 삭제
            $stmt = WeaponQuery::deleteWeaponCreate($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 무기 제작 시간 갱신
            $data['create_time'] = $createFinishTime;
            $stmt = WeaponQuery::updateWeaponWithCreateTime($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }*/

    /**
     * @param int $weaponId
     * @param int $currentLevel
     * @param string $date
     * @throws CtxException|Exception
     */
    public static function upgradeWeapon(int $weaponId, int $currentLevel, string $date)
    {
        $container = new WeaponDAO();
        $container->weaponId = $weaponId;
        $container->level = $currentLevel;
        $container->toLevel = $currentLevel + 1;
        $container->upgradeTime = $date;

        // 건물 업그레이드 job 생성
        $stmt = WeaponQuery::updateWeaponWithLevel($container);
        if ($stmt->rowCount() === 0) {
            (new CtxException())->insertFail();
        }
    }

    /*
    public static function resolveUpgradeBuilding(array $data)
    {
        // 기존 무기 업그레이드 정보 검색
        $stmt = WeaponQuery::selectWeapon($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        $toLevel = $res['to_level'];
        $upgradeTime = $res['upgrade_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 업그레이드 job 삭제
            $stmt = WeaponQuery::deleteWeaponUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 업그레이드 레벨 갱신
            $data['upgrade'] = $toLevel;
            $data['upgrade_time'] = $upgradeTime;
            $stmt = WeaponQuery::updateWeaponWithUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }*/
}
