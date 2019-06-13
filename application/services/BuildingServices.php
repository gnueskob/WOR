<?php

namespace lsb\App\services;

use lsb\App\models\BuildingDAO;
use lsb\App\query\BuildingQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use lsb\Libs\Timezone;
use PDOException;
use PDOStatement;

class BuildingServices extends Update
{
    /* @return BuildingDAO */
    protected static function getContainer()
    {
        return parent::getContainer();
    }

    protected static function getNewContainer()
    {
        return new BuildingDAO();
    }

    protected static function updateAll($container, $assign): PDOStatement
    {
        return BuildingQuery::updateBuildingAll(self::getContainer(), $assign);
    }

    public static function watchBuildingId(int $buildingId)
    {
        self::getContainer()->buildingId = $buildingId;
        return new self();
    }

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

        $stmt = BuildingQuery::selectBuildingsByUser($buildingContainer);
        $res = $stmt->fetchAll();
        $res = $res === false ? [] : $res;
        foreach ($res as $key => $value) {
            $res[$key] = new BuildingDAO($value);
        }
        return $res;
    }

    /**
     * @param BuildingDAO $container
     * @return int
     * @throws CtxException|Exception
     */
    public static function createBuilding(BuildingDAO $container)
    {
        // 건물 데이터 추가
        try {
            $stmt = BuildingQuery::insertBuilding($container);
            CtxException::insertFail($stmt->rowCount() === 0);
            return DB::getLastInsertId();
        } catch (PDOException $e) {
            // Unique key 중복 제한으로 걸릴 경우 따로 처리
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                return -1;
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param int $currentLevel
     * @param string $upgradeTime
     * @return BuildingServices
     */
    public static function upgradeBuilding(int $currentLevel, string $upgradeTime)
    {
        $container = self::getContainer();
        $container->level = $currentLevel;
        $container->toLevel = $currentLevel + 1;
        $container->upgradeTime = $upgradeTime;
        $container->updateProperty(['level', 'toLevel', 'upgradeTime']);
        return new self();
    }

    /**
     * @param int $manpower
     * @param string $deployTime
     * @return BuildingServices
     */
    public static function deployBuilding(int $manpower, string $deployTime)
    {
        $container = self::getContainer();
        $container->manpower = $manpower;
        $container->deployTime = $deployTime;
        $container->updateProperty(['manpower', 'deployTime']);
        return new self();
    }

    public static function modifyBuildingManpower(int $manpower)
    {
        $container = self::getContainer();
        $container->manpower = $manpower;
        $container->updateProperty(['manpower']);
        return new self();
    }

    /*************************************************************/

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

        $stmt = // TODO:
    }
}
