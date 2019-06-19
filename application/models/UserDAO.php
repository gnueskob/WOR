<?php

namespace lsb\App\models;

use lsb\Libs\Timezone;
use Exception;

class UserDAO extends DAO
{
    public static $dbColumToPropertyMap = [
        'user_id' => 'userId',
        'hive_id' => 'hiveId',
        'hive_uid' => 'hiveUid',
        'register_date' => 'registerDate',
        'country' => 'country',
        'lang' => 'lang',
        'os_version' => 'osVersion',
        'device_name' => 'deviceName',
        'app_version' => 'appVersion',
        'last_visit' => 'lastVisit',
        'territory_id' => 'territoryId',
        'name' => 'name',
        'castle_level' => 'castleLevel',
        'castle_to_level' => 'castleToLevel',
        'upgrade_time' => 'upgradeTime',
        'penalty_finish_time' => 'penaltyFinishTime',
        'auto_generate_manpower' => 'autoGenerateManpower',
        'manpower' => 'manpower',
        'appended_manpower' => 'appendedManpower',
        'tactical_resource' => 'tacticalResource',
        'food_resource' => 'foodResource',
        'luxury_resource' => 'luxuryResource',
        'friend_attack' => 'friendAttack',
        'war_request' => 'warRequest',
        'war_victory' => 'warVictory',
        'war_defeated' => 'warDefeated',
        'despoil_defense_success' => 'despoilDefenseSuccess',
        'despoil_defense_fail' => 'despoilDefenseFail',
        'boss1_kill_count' => 'boss1KillCount',
        'boss2_kill_count' => 'boss2KillCount',
        'boss3_kill_count' => 'boss3KillCount'
    ];

    private static $propertyToDBColumnMap = [];
    public static function getColumnMap()
    {
        if (empty(self::$propertyToDBColumnMap)) {
            self::$propertyToDBColumnMap = array_flip(self::$dbColumToPropertyMap);
        }
        return self::$propertyToDBColumnMap;
    }

    // platform
    public $userId;
    public $hiveId;
    public $hiveUid;
    public $registerDate;
    public $country;
    public $lang;
    public $osVersion;
    public $deviceName;
    public $appVersion;

    // info
    public $lastVisit;
    public $territoryId;
    public $name;
    public $castleLevel;
    public $castleToLevel;
    public $upgradeTime;
    public $penaltyFinishTime;
    public $autoGenerateManpower;
    public $manpower;
    public $appendedManpower;
    public $tacticalResource;
    public $foodResource;
    public $luxuryResource;
    public $friendAttack;

    // statistical
    public $warRequest;
    public $warVictory;
    public $warDefeated;
    public $despoilDefenseSuccess;
    public $despoilDefenseFail;
    public $boss1KillCount;
    public $boss2KillCount;
    public $boss3KillCount;

    // hidden property
    public $currentCastleLevel;
    public $availableManpower;
    public $usedManpower;

    /**
     * UserDAO constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data, self::$dbColumToPropertyMap);
        if (isset($this->upgradeTime) &&
            $this->upgradeTime <= Timezone::getNowUTC()) {
            $this->currentCastleLevel = $this->castleToLevel;
        } else {
            $this->currentCastleLevel = $this->castleLevel;
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

    public function hasSufficientResource(int $tatical, int $food, int $luxury)
    {
        return $this->tacticalResource >= $tatical &&
                $this->foodResource >= $food &&
                $this->luxuryResource >= $luxury;
    }

    public function hasSUfficientFood(int $food)
    {
        return $this->foodResource >= $food;
    }

    public function hasSufficientAvailableManpower(int $manpower)
    {
        return $this->availableManpower > $manpower;
    }
}
