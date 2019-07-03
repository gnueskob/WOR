<?php

namespace lsb\App\models;

use lsb\App\query\BuildingQuery;
use lsb\Libs\CtxException as CE;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;

class BuildingDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'building_id' => 'buildingId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
        'tile_id' => 'tileId',
        'building_type' => 'buildingType',
        'resource_type' => 'resourceType',
        'create_time' => 'createTime',
        'deploy_time' => 'deployTime',
        'upgrade_time' => 'upgradeTime',
        'level' => 'level',
        'to_level' => 'toLevel',
        'manpower' => 'manpower',
        'last_update' => 'lastUpdate'
    ];

    private static $propertyToDBColumnMap = [];
    public static function getColumnMap()
    {
        if (empty(self::$propertyToDBColumnMap)) {
            self::$propertyToDBColumnMap = array_flip(self::$dbColumToPropertyMap);
        }
        return self::$propertyToDBColumnMap;
    }

    public $buildingId;
    public $userId;
    public $territoryId;
    public $tileId;
    public $buildingType;
    public $resourceType;

    public $createTime;
    public $deployTime;
    public $upgradeTime;
    public $level;
    public $toLevel;
    public $manpower;
    public $lastUpdate;

    // hidden property
    public $currentLevel = 1;

    /**
     * BuildingDAO constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data, self::$dbColumToPropertyMap);
        if (isset($this->upgradeTime) &&
            $this->upgradeTime <= Timezone::getNowUTC()) {
            $this->currentLevel = $this->toLevel;
        } else {
            $this->currentLevel = $this->level;
        }
    }

    /*****************************************************************************************************************/
    // check function

    /**
     * @return bool
     * @throws Exception
     */
    public function isUpgrading()
    {
        return isset($this->upgradeTime) && $this->upgradeTime > Timezone::getNowUTC();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isUpgraded()
    {
        return isset($this->upgradeTime) && $this->upgradeTime <= Timezone::getNowUTC();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isDeploying()
    {
        return isset($this->deployTime) && $this->deployTime > Timezone::getNowUTC();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isDeployed()
    {
        return isset($this->deployTime) && $this->deployTime <= Timezone::getNowUTC();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isCreating()
    {
        return isset($this->createTime) && $this->createTime > Timezone::getNowUTC();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isCreated()
    {
        return isset($this->createTime) && $this->createTime <= Timezone::getNowUTC();
    }

    /*****************************************************************************************************************/
    // set building

    public static function container(int $buildingId = 0)
    {
        $building = new BuildingDAO();
        $building->buildingId = $buildingId;
        return $building;
    }

    /**
     * @param float $upgradeUnitTime
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function upgrade(float $upgradeUnitTime, bool $pending = false)
    {
        $this->level = $this->currentLevel;
        $this->toLevel = $this->currentLevel + 1;
        $this->upgradeTime = Timezone::getCompleteTime($upgradeUnitTime);

        $query = BuildingQuery::qSetUpgradeFromBuilding($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /**
     * @param int $manpower
     * @param float $deployUnitTime
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function deploy(int $manpower, float $deployUnitTime, bool $pending = false)
    {
        $this->manpower = $manpower;
        $this->deployTime = Timezone::getCompleteTime($deployUnitTime);

        $query = BuildingQuery::qSetDeployFromBuilding($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /**
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function cancelDeploy(bool $pending = false)
    {
        $this->manpower = 0;
        $this->deployTime = null;

        $query = BuildingQuery::qSetDeployFromBuilding($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /**
     * @param int $userId
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function resetBuildingsManpower(int $userId, bool $pending = false)
    {
        $this->userId = $userId;
        $this->manpower = 0;
        $this->deployTime = null;

        $query = BuildingQuery::qSetDeployFromBuildingByUser($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /*****************************************************************************************************************/
    // get building record

    /**
     * @param PDOStatement $stmt
     * @return BuildingDAO
     * @throws Exception
     */
    private static function getBuildingDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new BuildingDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return BuildingDAO[]
     * @throws Exception
     */
    private static function getBuildingDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new BuildingDAO($row);
        }
        return $res;
    }

    /**
     * @param int $buildingId
     * @return BuildingDAO
     * @throws Exception
     */
    public static function getBuilding(int $buildingId)
    {
        $dao = new BuildingDAO();
        $dao->buildingId = $buildingId;

        $stmt = BuildingQuery::qSelectBuilding($dao)->run();
        $building = static::getBuildingDAO($stmt);

        CE::check($building->isEmpty(), ErrorCode::INVALID_BUILDING);
        return $building;
    }

    /**
     * @param int $userId
     * @return BuildingDAO[]
     * @throws Exception
     */
    public static function getBuildings(int $userId)
    {
        $dao = new BuildingDAO();
        $dao->userId = $userId;

        $stmt = BuildingQuery::qSelectBuildings($dao)->run();
        $buildings = static::getBuildingDAOs($stmt);

        return $buildings;
    }

    /**
     * @param int $userId
     * @return BuildingDAO[]
     * @throws Exception
     */
    public static function getDeployedBuildings(int $userId)
    {
        $dao = new BuildingDAO();
        $dao->userId = $userId;
        $dao->deployTime = Timezone::getNowUTC();

        $stmt = BuildingQuery::qSelectActiveBuildings($dao)->run();
        $buildings = static::getBuildingDAOs($stmt);

        return $buildings;
    }

    /*****************************************************************************************************************/
    // create new record

    /**
     * @param array $param
     * @return int
     * @throws Exception
     */
    public static function createBuilding(array $param)
    {
        $dao = new BuildingDAO();
        $dao->buildingId = null;
        $dao->userId = $param['userId'];
        $dao->territoryId = $param['territoryId'];
        $dao->tileId = $param['tileId'];
        $dao->buildingType = $param['buildingType'];
        $dao->createTime = Timezone::getCompleteTime($param['createUnitTime']);
        $dao->upgradeTime = null;
        $dao->deployTime = null;
        $dao->level = 1;
        $dao->toLevel = 1;
        $dao->lastUpdate = Timezone::getNowUTC();

        $stmt = BuildingQuery::qInsertBuilding($dao)
            ->checkError([DB::DUPLICATE_ERRORCODE])
            ->run();

        $errorCode = static::resolveInsert($stmt);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::ALREADY_USED_TILE);

        $buildingId = DB::getLastInsertId();

        return $buildingId;
    }
}
