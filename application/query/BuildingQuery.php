<?php

namespace lsb\App\query;

use lsb\App\models\BuildingDAO;

class BuildingQuery extends Query
{
    public function __construct()
    {
        parent::__construct(BuildingDAO::getColumnMap());
    }

    public static function building()
    {
        return static::make()->setTable('building');
    }

    /************************************************************/

    public function whereUserId(int $userId)
    {
        return $this->whereEqual(['userId' => $userId]);
    }

    public function whereBuildingId(int $buildingId)
    {
        return $this->whereEqual(['buildingId' => $buildingId]);
    }

    public function whereDeployed(string $time)
    {
        return $this->whereLT(['deployTime' => $time]);
    }

    public function whereType(int $buildingType)
    {
        return $this->whereEqual(['buildingType' => $buildingType]);
    }

    /************************************************************/

    // SELECT QUERY

    public static function qSelectBuilding(BuildingDAO $dao)
    {
        return static::building()
            ->selectQurey()
            ->selectAll()
            ->whereBuildingId($dao->buildingId);
    }

    public static function qSelectBuildings(BuildingDAO $dao)
    {
        return static::building()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId);
    }

    public static function qSelectActiveBuildings(BuildingDAO $dao)
    {
        return static::building()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId)
            ->whereDeployed($dao->deployTime);
    }

    /************************************************************/

    // INSERT QUERY

    public static function qInsertBuilding(BuildingDAO $dao)
    {
        return static::building()
            ->insertQurey()
            ->value([
                'buildingId' => $dao->buildingId,
                'userId' => $dao->userId,
                'territoryId' => $dao->territoryId,
                'tileId' => $dao->tileId,
                'buildingType' => $dao->buildingType,
                'createTime' => $dao->createTime,
                'upgradeTime' => $dao->upgradeTime,
                'deployTime' => $dao->deployTime,
                'level' => $dao->level,
                'toLevel' => $dao->toLevel,
                'manpower' => $dao->manpower,
                'lastUpdate' => $dao->lastUpdate
            ]);
    }

    /************************************************************/

    // UPDATE QUERY

    public static function qSetUpgradeFromBuilding(BuildingDAO $dao)
    {
        return static::building()
            ->updateQurey()
            ->set([
                'level' => $dao->level,
                'toLevel' => $dao->toLevel,
                'upgradeTime' => $dao->upgradeTime
            ])
            ->whereBuildingId($dao->buildingId);
    }

    public static function qSetDeployFromBuilding(BuildingDAO $dao)
    {
        return static::building()
            ->updateQurey()
            ->set([
                'manpower' => $dao->manpower,
                'deployTime' => $dao->deployTime
            ])
            ->whereBuildingId($dao->buildingId);
    }

    public static function qSetDeployFromBuildingByUser(BuildingDAO $dao)
    {
        return static::building()
            ->updateQurey()
            ->set([
                'manpower' => $dao->manpower,
                'deployTime' => $dao->deployTime
            ])
            ->whereUserId($dao->userId);
    }
}
