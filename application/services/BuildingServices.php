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
        $building = static::getBuildingDAO($stmt);
        CtxException::invalidBuilding($building->isEmpty());
        return $building;
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
     * @param float $createUnitTime
     * @return int
     * @throws CtxException|Exception
     */
    public static function create(
        int $userId,
        int $territoryId,
        int $tileId,
        int $buildingType,
        float $createUnitTime
    ) {
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
     * @param float $upgradeUnitTime
     * @param bool $pending
     * @throws CtxException
     */
    public static function upgrade(
        int $buildingId,
        int $currentLevel,
        float $upgradeUnitTime,
        bool $pending = false
    ) {
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
     * @param float $deployUnitTime
     * @param bool $pending
     * @throws CtxException
     */
    public static function deploy(
        int $buildingId,
        int $manpower,
        float $deployUnitTime,
        bool $pending = false
    ) {
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
    public static function cancelDeploy(int $buildingId, bool $pending = false)
    {
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
     * @param int $deployMinManpower
     * @throws CtxException
     */
    public static function checkBuildingManpowerMin(BuildingDAO $building, int $deployManpower, int $deployMinManpower)
    {
        $isInsufficient = $building->manpower + $deployManpower < $deployMinManpower;
        CtxException::manpowerInsufficient($isInsufficient);
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

    /**
     * @param int $userId
     * @return int
     * @throws Exception
     */
    public static function getUsedManpower(int $userId)
    {
        Plan::getDataAll(PLAN_BUILDING);

        $usedManpower = 0;
        $buildings = static::getBuildingsByUser($userId);
        foreach ($buildings as $building) {
            if ($building->isCreating()) {
                list($createManpower) = Plan::getBuildingManpower($building->buildingType);
                $usedManpower += $createManpower;
            } else {
                $usedManpower += $building->manpower;
            }
        }
        return $usedManpower;
    }

    /**
     * @param int $userId
     * @param string $lastVisit
     * @return array
     * @throws Exception
     */
    public static function generateResources(int $userId, string $lastVisit)
    {
        $dao = new BuildingDAO();
        $dao->userId = $userId;
        $dao->deployTime = Timezone::getNowUTC();

        $stmt = BuildingQuery::qSelectBuildings($dao)->run();
        $buildings = static::getBuildingDAOs($stmt);

        Plan::getDataAll(PLAN_RESOURCE);

        $tactical = 0;
        $food = 0;
        $luxury = 0;
        $lastTime = Timezone::getTimestampFromString($lastVisit);
        $unitTime = Plan::getUnitTime();
        $now = Timezone::getNowUTCTimestamp();

        foreach ($buildings as $building) {
            if ($building->resourceType === 0) {
                continue;
            }

            $resourceClass = Plan::getResourceClass($building->resourceType);
            $ppu = Plan::getResourcePPU($building->resourceType);

            if ($building->isDeployed()) {
                $elapsedUnitTime = (int) (($now - $lastTime) / $unitTime);
            } elseif ($building->isDeploying()) {
                $deployedTime = Timezone::getTimestampFromString($building->deployTime);
                $elapsedUnitTime = (int) (($now - $deployedTime) / $unitTime);
            } else {
                continue;
            }

            if ($resourceClass === PLAN_RESOURCE_CLASS_FOOD) {
                $food += $ppu * $elapsedUnitTime;
            } elseif ($resourceClass === PLAN_RESOURCE_CLASS_TACTICAL) {
                $tactical += $ppu * $elapsedUnitTime;
            } elseif ($resourceClass === PLAN_RESOURCE_CLASS_LUXURY) {
                $luxury += $ppu * $elapsedUnitTime;
            }
        }

        return [$tactical, $food, $luxury];
    }


    /**
     * @param int $userId
     * @return array|null
     * @throws Exception
     */
    public static function getArmyManpowerAndAttack(int $userId)
    {
        $dao = new BuildingDAO();
        $dao->userId = $userId;
        $dao->buildingType = PLAN_BUILDING_ID_ARMY;
        $dao->deployTime = Timezone::getNowUTC();

        $stmt = BuildingQuery::qSelectActiveBuildings($dao)->run();
        $activeBuildings = static::getBuildingDAOs($stmt);

        $totalManpower = 0;
        $totalAttack = 0;

        foreach ($activeBuildings as $building) {
            if ($building->buildingType !== PLAN_BUILDING_ID_ARMY) {
                continue;
            }
            $totalAttack += $building->manpower * $building->currentLevel;
            $totalManpower += $building->manpower;
        }

        return [$totalManpower, $totalAttack];
    }

    /**
     * @param int $userId
     * @return float|int|mixed
     * @throws Exception
     */
    public static function getDefensePower(int $userId)
    {
        $dao = new BuildingDAO();
        $dao->userId = $userId;
        $dao->buildingType = PLAN_BUILDING_ID_TOWER;
        $dao->deployTime = Timezone::getNowUTC();

        $stmt = BuildingQuery::qSelectActiveBuildings($dao)->run();
        $activeBuildings = static::getBuildingDAOs($stmt);

        $totalDefense = 0;

        foreach ($activeBuildings as $building) {
            if ($building->buildingType !== PLAN_BUILDING_ID_TOWER) {
                continue;
            }
            $totalDefense += Plan::getBuildingDefense(PLAN_BUILDING_ID_TOWER, $building->currentLevel);
        }

        return $totalDefense;
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public static function resetBuildingsManpower(int $userId)
    {
        $dao = new BuildingDAO();
        $dao->userId = $userId;
        $dao->manpower = 0;
        $dao->deployTime = null;

        $query = BuildingQuery::qSetDeployFromBuildingByUser($dao);
        self::validateUpdate($query, false);
    }
}
