<?php

namespace lsb\App\services;

use lsb\App\query\UserQuery;
use lsb\App\query\WeaponQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use PDOException;

class WeaponServices
{
    /**
     * @param array $data
     * @return mixed
     * @throws CtxException
     */
    public static function getWeapon(array $data)
    {
        $stmt = WeaponQuery::selectWeapon($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        return $res;
    }

    /**
     * @param array $data
     * @return mixed
     * @throws CtxException
     */
    public static function getWeaponsByUser(array $data)
    {
        $stmt = WeaponQuery::selectWeaponsByUser($data);
        $res = $stmt->fetchAll();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        foreach ($res as $key => $value) {
            $res[$key] = DB::trimColumn($value);
        }
        return $res;
    }

    /**
     * @param array $data
     * @return array|bool|mixed
     * @throws Exception
     */
    public static function createWeapon(array $data)
    {
        $db = DB::getInstance()->getDBConnection();

        try {
            $db->beginTransaction();

            // 필요한 자원량 소모시키기
            $stmt = UserQuery::updateUserInfoWithResource($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            // 무기 데이터 추가
            $stmt = WeaponQuery::insertWeapon($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            // 무기 생성 job 추가
            $weaponId = $db->lastInsertId();
            $data['weapon_id'] = $weaponId;
            $stmt = WeaponQuery::insertWeaponCreate($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }

            return $weaponId;
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @throws CtxException
     */
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
    }

    /**
     * @param array $data
     * @throws CtxException
     */
    public static function upgradeWeapon(array $data)
    {
        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 건물 업그레이드에 필요한 유저 자원 소모
            $stmt = UserQuery::updateUserInfoWithResource($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            // 건물 업그레이드 job 생성
            $stmt = WeaponQuery::insertWeaponUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @return int
     * @throws Exception
     */
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
    }
}
