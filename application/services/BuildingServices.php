<?php

namespace lsb\App\services;

use lsb\App\query\BuildingQuery;
use lsb\App\query\UserQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use PDOException;

class BuildingServices
{
    /**
     * @param array $data
     * @return array
     * @throws CtxException
     */
    public static function getBuilding(array $data)
    {
        $stmt = BuildingQuery::selectBuilding($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        return DB::trimColumn($res);
    }

    /**
     * @param array $data
     * @return array
     * @throws CtxException
     */
    public static function getBuildingsByUser(array $data)
    {
        $stmt = BuildingQuery::selectBuildingByUser($data);
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
    public static function createBuilding(array $data)
    {
        $db = DB::getInstance()->getDBConnection();

        try {
            $db->beginTransaction();

            // 필요한 자원량 소모시키기
            $stmt = UserQuery::updateUserInfoWithResource($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            // 건물 데이터 추가
            $stmt = BuildingQuery::insertBuilding($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            // 건물 생성 job 추가
            $buildingId = $db->lastInsertId();
            $data['building_id'] = $buildingId;
            $stmt = BuildingQuery::insertBuildingCreate($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }

            return $buildingId;
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
        $stmt = BuildingQuery::selectBuilding($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        $createFinishTime = $res['create_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 건물 건설 job 삭제
            $stmt = BuildingQuery::deleteBuildingCreate($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 건물 건설 시간 갱신
            $data['create_time'] = $createFinishTime;
            $stmt = BuildingQuery::updateBuildingWithCreateTime($data);
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
    public static function upgradeBuilding(array $data)
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
            $stmt = BuildingQuery::insertBuildingUpgrade($data);
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
        // 기존 건물 업그레이드 정보 검색
        $stmt = BuildingQuery::selectBuilding($data);
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
            $stmt = BuildingQuery::deleteBuildingUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 업그레이드 레벨 갱신
            $data['upgrade'] = $toLevel;
            $data['upgrade_time'] = $upgradeTime;
            $stmt = BuildingQuery::updateBuildingWithUpgrade($data);
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
    public static function deployBuilding(array $data)
    {
        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 유저 가용 인력 정보 갱신
            $stmt = UserQuery::updateUserInfoWithUsedManpower($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            // 인구 배치 job 생성
            $stmt = BuildingQuery::insertBuildingDeploy($data);
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
    public static function resolveDeployBuilding(array $data)
    {
        // 기존 인구 배치 정보 검색
        $stmt = BuildingQuery::selectBuilding($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        $deployFinishTime = $res['deploy_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 인구 배치 job 삭제
            $stmt = BuildingQuery::deleteBuildingDeploy($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 인구 배치 시간 갱신
            $data['deploy_time'] = $deployFinishTime;
            $stmt = BuildingQuery::updateBuildingWithDeployTimeManpower($data);
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
