<?php

namespace lsb\App\models;

use lsb\Libs\Timezone;
use Exception;

class BuildingDAO extends DAO
{
    /*
    private static $propertyToDBColumnMap = [];
    */
    private static $dbColumToPropertyMap = [
        'building_id' => 'buildingId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
        'tile_id' => 'tileId',
        'building_type' => 'buildingType',
        'create_time' => 'createTime',
        'deploy_time' => 'deployTime',
        'upgrade_time' => 'upgradeTime',
        'level' => 'level',
        'toLevel' => 'to_level',
        'manpower' => 'manpower',
        'last_update' => 'lastUpdate'
    ];

    public $buildingId;
    public $userId;
    public $territoryId;
    public $tileId;
    public $buildingType;

    public $createTime;
    public $deployTime;
    public $upgradeTime;
    public $level;
    public $toLevel;
    public $manpower;
    public $lastUpdate;

    // hidden property
    public $currentLevel = 1;
    public $activated = false;

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
        if (isset($this->deployTime) &&
            $this->deployTime < Timezone::getNowUTC()) {
            $this->activated = true;
        }
    }

    /*
    public function getDBColumnToPropertyMap()
    {
        return self::$dbColumToPropertyMap;
    }

    public function getPropertyToDBColumnMap()
    {
        if (count(self::$propertyToDBColumnMap) === 0) {
            self::$propertyToDBColumnMap = array_flip(self::$dbColumToPropertyMap);
        }
        return self::$propertyToDBColumnMap;
    }*/

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
}
