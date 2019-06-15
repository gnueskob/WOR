<?php

namespace lsb\App\services;

use lsb\App\models\BuildingDAO;
use lsb\App\models\UserDAO;
use lsb\App\query\BuildingQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;
use PDOException;
use PDOStatement;

class BuildingServices extends Services
{

    /**
     * @param int $userId
     * @return BuildingDAO
     * @throws Exception
     */
    public static function getBuilding(int $userId)
    {
        $dao = new BuildingDAO();
        $dao->userId = $userId;

        $stmt = BuildingQuery::qSelectBuilding($dao)->run();
        return static::getBuildingDAO($stmt);
    }

    /**
     * @param int $userId
     * @return BuildingDAO[]|bool
     * @throws Exception
     */
    public static function getBuildingsByUser(int $userId)
    {
        $dao = new BuildingDAO();
        $dao->userId = $userId;

        $stmt = BuildingQuery::qSelectBuildings($dao)->run();
        return static::getBuildingDAOs($stmt);
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @param int $tileId
     * @param int $buildingType
     * @param string $createUnitTime
     * @return int
     * @throws CtxException|Exception
     */
    public static function create(
        int $userId,
        int $territoryId,
        int $tileId,
        int $buildingType,
        string $createUnitTime)
    {
        $dao = new BuildingDAO();
        $dao->buildingId = null;
        $dao->userId = $userId;
        $dao->territoryId = $territoryId;
        $dao->tileId = $tileId;
        $dao->buildingType = $buildingType;
        $dao->createTime = Timezone::getCompleteTime($createUnitTime);
        $dao->upgradeTime = null;
        $dao->deployTime = null;
        $dao->level = 1;
        $dao->toLevel = 1;
        $dao->lastUpdate = Timezone::getNowUTC();

        $stmt = BuildingQuery::qInsertBuilding($dao)
            ->checkError([DUPLICATE_ERRORCODE])
            ->run();
        CtxException::alreadyUsedTile($stmt === DUPLICATE_ERRORCODE); // 이미 사용중인 타일
        static::validateInsert($stmt);
        $buildingId = DB::getLastInsertId();

        return $buildingId;
    }

    /*********************************************************/

    // CHANGE BUILDING DATA

    /**
     * @param int $buildingId
     * @param int $currentLevel
     * @param int $upgradeUnitTime
     * @param bool $pending
     * @throws CtxException
     */
    public static function upgrade(
        int $buildingId,
        int $currentLevel,
        int $upgradeUnitTime,
        bool $pending = false)
    {
        $dao = new BuildingDAO();
        $dao->buildingId = $buildingId;

        $dao->level = $currentLevel;
        $dao->toLevel = $currentLevel + 1;

        $dao->upgradeTime = Timezone::getCompleteTime($upgradeUnitTime);

        $query = BuildingQuery::qSetUpgradeFromBuilding($dao);
        static::validateUpdate($query, $pending);
    }

    /**
     * @param int $buildingId
     * @param int $manpower
     * @param int $deployUnitTime
     * @param bool $pending
     * @throws CtxException
     */
    public static function deploy(
        int $buildingId,
        int $manpower,
        int $deployUnitTime,
        bool $pending = false)
    {
        $dao = new BuildingDAO();
        $dao->buildingId = $buildingId;

        $dao->manpower = $manpower;
        $dao->deployTime = Timezone::getCompleteTime($deployUnitTime);

        $query = BuildingQuery::qSetDeployFromBuilding($dao);
        static::validateUpdate($query, $pending);
    }

    /**
     * @param int $buildingId
     * @param bool $pending
     * @throws CtxException
     */
    public static function cancelDeploy(int $buildingId, bool $pending = false) {
        $dao = new BuildingDAO();
        $dao->buildingId = $buildingId;

        $dao->manpower = 0;
        $dao->deployTime = null;

        $query = BuildingQuery::qSetDeployFromBuilding($dao);
        static::validateUpdate($query, $pending);
    }

    /*************************************************************/

    // CHECK

    /**
     * @param BuildingDAO $building
     * @throws Exception
     */
    public static function checkCreateFinished(BuildingDAO $building)
    {
        CtxException::notCreatedYet(!$building->isCreated());
    }

    /**
     * @param BuildingDAO $building
     * @throws Exception
     */
    public static function checkUpgradeStatus(BuildingDAO $building)
    {
        CtxException::notCompletedPreviousJobYet($building->isUpgrading());
    }

    /**
     * @param BuildingDAO $building
     * @throws Exception
     */
    public static function checkUpgradeFinished(BuildingDAO $building)
    {
        CtxException::notUpgradedYet(!$building->isUpgraded());
    }

    /**
     * @param BuildingDAO $building
     * @throws Exception
     */
    public static function checkDeplpoyStatus(BuildingDAO $building)
    {
        CtxException::notCompletedPreviousJobYet($building->isDeploying());
    }

    /**
     * @param BuildingDAO $building
     * @throws Exception
     */
    public static function checkDeployeFinished(BuildingDAO $building)
    {
        CtxException::notDeployedYet(!$building->isDeployed());
    }

    /**
     * @param BuildingDAO $building
     * @throws CtxException
     */
    public static function checkUpgradableType(BuildingDAO $building)
    {
        $upgradableBuildingType = [
            PLAN_BUILDING_ID_TOWER,
            PLAN_BUILDING_ID_ARMY,
            PLAN_BUILDING_ID_CASTLE
        ];
        CtxException::notUpgradable(in_array($building->buildingType, $upgradableBuildingType));
    }

    /**
     * @param BuildingDAO $building
     * @param int $deployManpower
     * @param int $manpowerLimit
     * @throws CtxException
     */
    public static function checkBuildingManpowerOver(BuildingDAO $building, int $deployManpower, int $manpowerLimit)
    {
        $isOver = $building->manpower + $deployManpower > $manpowerLimit;
        CtxException::exceedManpowerBuilding($isOver);
    }

    /*************************************************************/

    /*
    public static function getManpower(int $userId)
    {
        $dao = new BuildingDAO();
        $dao->userId = $userId;

        $stmt = BuildingQuery::qSumManpowerFromBuilding($dao)->run();
        $res = $stmt->fetch();
        return $res === false ? 0 : $res['totalManpower'];
    }*/

    public static function parseArmyManpower(array $armyManpower)
    {
        $manpowerList = [];
        $buildingIds = [];
        foreach ($armyManpower as $value) {
            $manpowerList[$value['building_id']] = $value['manpower'];
            $buildingIds[] = $value['building_id'];
        }
        return [$manpowerList, $buildingIds];
    }


    /**
     * @param int $userId
     * @return array|null
     * @throws CtxException|Exception
     */
    public static function getArmyManpower(int $userId)
    {
        $buildings = self::getBuildingsByUser($userId);

        $totalManpower = 0;
        $totalAttack = 0;

        /* @var BuildingDAO[] $buildings */
        foreach ($buildings as $building) {
            CtxException::invalidId($building->userId !== $userId);

            if (!$building->isDeployed() &&
                $building->buildingType !== PLAN_BUILDING_ID_ARMY) {
                continue;
            }

            $totalAttack += $building->manpower * $building->currentLevel;
            $totalManpower += $building->manpower;
        }

        return [$totalManpower, $totalAttack];
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public static function resetBuildingsManpower(int $userId)
    {
        $container = new BuildingDAO();
        $container->userId = $userId;
        $container->deployTime = Timezone::getNowUTC();

    }
}
