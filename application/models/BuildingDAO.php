<?php

namespace lsb\App\models;

use lsb\Libs\Timezone;
use Exception;

class BuildingDAO extends DAO
{
    public static $propertyToDBColumnMap = [];
    public static $dbColumToPropertyMap = [
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
    public $currentLevel;

    /**
     * BuildingDAO constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data = [])
    {
        if (count($data) === 0) {
            if (count(self::$propertyToDBColumnMap) === 0) {
                self::$propertyToDBColumnMap = array_flip(self::$dbColumToPropertyMap);
            }
            return;
        }

        parent::__construct($data, self::$dbColumToPropertyMap);
        if (isset($this->upgradeTime) &&
            $this->upgradeTime <= Timezone::getNowUTC()) {
            $this->currentLevel = $this->toLevel;
        } else {
            $this->currentLevel = $this->level;
        }
    }
}
