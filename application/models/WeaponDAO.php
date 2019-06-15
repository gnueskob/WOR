<?php

namespace lsb\App\models;

use lsb\Libs\Timezone;
use Exception;

class WeaponDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'weapon_id' => 'weaponId',
        'user_id' => 'userId',
        'weapon_type' => 'weaponType',
        'create_time' => 'createTime',
        'upgrade_time' => 'upgradeTime',
        'level' => 'level',
        'toLevel' => 'to_level',
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

    public $weaponId;
    public $userId;
    public $weaponType;

    public $createTime;
    public $upgradeTime;
    public $level;
    public $toLevel;
    public $lastUpdate;

    // hidden property
    public $currentLevel;

    /**
     * WeaponDAO constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data = [])
    {
        if (count($data) === 0) {
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
    public function isCreated()
    {
        return isset($this->createTime) && $this->createTime <= Timezone::getNowUTC();
    }
}
