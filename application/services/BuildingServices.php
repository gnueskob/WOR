<?php

namespace lsb\App\services;

use lsb\App\models\BuildingDAO;
use lsb\App\query\BuildingQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use lsb\Libs\Timezone;
use PDOException;

class BuildingServices
{
    /**
     * @param int $userId
     * @return BuildingDAO
     * @throws Exception
     */
    public static function getBuilding(int $userId)
    {
        $buildingContainer = new BuildingDAO();
        $buildingContainer->userId = $userId;

        $stmt = BuildingQuery::selectBuilding($buildingContainer);
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new BuildingDAO($res);
    }

    /**
     * @param int $userId
     * @return BuildingDAO[]|bool
     * @throws Exception
     */
    public static function getBuildingsByUser(int $userId)
    {
        $buildingContainer = new BuildingDAO();
        $buildingContainer->userId = $userId;

        $stmt = BuildingQuery::selectBuildingByUser($buildingContainer);
        $res = $stmt->fetchAll();
        if ($res === false) {
            return [];
        }
        foreach ($res as $key => $value) {
            $res[$key] = new BuildingDAO($value);
        }
        return $res;
    }

    /**
     * @param $userId
     * @param $tileId
     * @param $territoryId
     * @param $buildingType
     * @param $creatTime
     * @return int
     * @throws CtxException|Exception
     */
    public static function createBuilding(
        $userId,
        $tileId,
        $territoryId,
        $buildingType,
        $creatTime
    ) {
        $buildingContainer = new BuildingDAO();
        $buildingContainer->userId = $userId;
        $buildingContainer->tileId = $tileId;
        $buildingContainer->territoryId = $territoryId;
        $buildingContainer->buildingType = $buildingType;
        $buildingContainer->createTime = $creatTime;

        // 건물 데이터 추가
        try {
            $stmt = BuildingQuery::insertBuilding($buildingContainer);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }
            $db = DB::getInstance()->getDBConnection();
            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            // Unique key 중복 제한으로 걸릴 경우 따로 처리
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                return -1;
            } else {
                throw $e;
            }
        }
    }

    /*
     * @param array $data
     * @throws CtxException
     *
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
    }*/

    /**
     * @param int $buildingId
     * @param int $currentLevel
     * @param string $upgradeTime
     * @throws CtxException|Exception
     */
    public static function upgradeBuilding(
        int $buildingId,
        int $currentLevel,
        string $upgradeTime
    ) {
        $buildingContainer = new BuildingDAO();
        $buildingContainer->buildingId = $buildingId;
        $buildingContainer->level = $currentLevel;
        $buildingContainer->toLevel = $currentLevel + 1;
        $buildingContainer->upgradeTime = $upgradeTime;

        $stmt = BuildingQuery::updateBuildingWithLevel($buildingContainer);
        if ($stmt->rowCount() === 0) {
            (new CtxException())->updateFail();
        }
    }

    /*
     * @param array $data
     * @return int
     * @throws Exception

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
    }*/

    /**
     * @param int $userId
     * @param int $manpower
     * @param string $deployTime
     * @throws CtxException|Exception
     */
    public static function deployBuilding(
        int $userId,
        int $manpower,
        string $deployTime
    ) {
        $buildingContainer = new BuildingDAO();
        $buildingContainer->userId = $userId;
        $buildingContainer->manpower = $manpower;
        $buildingContainer->deployTime = $deployTime;

        $stmt = BuildingQuery::updateBuildingWithDeployTimeAndManpower($buildingContainer);
        if ($stmt->rowCount() === 0) {
            (new CtxException())->insertFail();
        }
    }

    /*
     * @param array $data
     * @return int
     * @throws Exception

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
    }*/

    /*************************************************************/

    /**
     * @param int $userId
     * @param array $armyManpower
     * @return array|null
     * @throws CtxException|Exception
     */
    public static function getArmyManpower(int $userId, array $armyManpower)
    {
        $manpowerList = [];
        $buildingIds = [];
        foreach ($armyManpower as $value) {
            $manpowerList[$value['building_id']] = $value['manpower'];
            $buildingIds[] = $value['building_id'];
        }

        $stmt = BuildingQuery::selectBuildingsById($buildingIds);
        $res = $stmt->fetchAll();
        if ($res === false) {
            return null;
        }
        $buildings = [];
        foreach ($res as $value) {
            $buildings[] = new BuildingDAO($value);
        }

        $totalManpower = 0;
        $totalAttack = 0;
        /* @var BuildingDAO[] $buildings */
        foreach ($buildings as $building) {
            if ($building->userId !== $userId) {
                (new CtxException())->invaildUser();
            }
            if ($building->buildingType !== PLAN_BUILDING_ID_ARMY) {
                (new CtxException())->invalidType();
            }
            if ($building->manpower < $manpowerList[$building->buildingId]) {
                (new CtxException())->manpowerInsufficient();
            }
            if (is_null($building->deployTime) ||
                $building->deployTime > Timezone::getNowUTC()) {
                continue;
            }
            $totalAttack += $manpowerList[$building->buildingId] * $building->currentLevel;
            $totalManpower += $building->manpower;
        }

        return [$totalManpower, $totalAttack];
    }
}
