<?php

namespace lsb\App\models;

use lsb\App\query\UserQuery;
use lsb\App\services\BuildingServices;
use lsb\App\services\WeaponServices;
use lsb\Libs\CtxException AS CE;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;

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

    public function hasResource(int $tatical, int $food, int $luxury)
    {
        return $this->tacticalResource >= $tatical &&
                $this->foodResource >= $food &&
                $this->luxuryResource >= $luxury;
    }

    public function hasAvailableManpower(int $manpower)
    {
        return $this->availableManpower > $manpower;
    }

    /*****************************************************************************************************************/
    // set user

    public static function container(int $userId)
    {
        $user = new UserDAO();
        $user->userId = $userId;
        return $user;
    }

    /**
     * @param bool $pending
     * @return $this
     * @throws Exception
     */
    public function setLastVisit(bool $pending = false)
    {
        $this->lastVisit = Timezone::getNowUTC();
        $query = UserQuery::qSetLastVisitFromUserInfo($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /**
     * @param string $name
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function setName(string $name, bool $pending = false)
    {
        $this->name = $name;
        $query = UserQuery::qSetNameFromUserInfo($this);
        $errorCode = $this->resolveUpdate($query, $pending, [DB::DUPLICATE_ERRORCODE]);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::DUPLICATE_NAME);
        return $this;
    }

    /**
     * @param int $territoryId
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function setTerritoryId(int $territoryId, bool $pending = false)
    {
        $this->territoryId = $territoryId;
        $query = UserQuery::qSetTerritoryIdFromUserInfo($this);
        $errorCode = $this->resolveUpdate($query, $pending, [DB::DUPLICATE_ERRORCODE]);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::DUPLICATE_TERRITORY);
        return $this;
    }

    /**
     * @param int $tactical
     * @param int $food
     * @param int $luxury
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function useResources(int $tactical, int $food, int $luxury, bool $pending = false)
    {
        $this->tacticalResource = $tactical;
        $this->foodResource = $food;
        $this->luxuryResource = $luxury;

        $query = UserQuery::qSubtarctResourcesFromUserInfo($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /**
     * @param int $manpower
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function useManpower(int $manpower, bool $pending = false)
    {
        $this->manpower = $manpower;

        $query = UserQuery::qSubtractManpowerFromUserInfo($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /**
     * @param int $upgradeUnitTime
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function upgradeCastleLevel(int $upgradeUnitTime, bool $pending = false)
    {
        $this->castleLevel = $this->currentCastleLevel;
        $this->castleToLevel = $this->currentCastleLevel + 1;
        $this->upgradeTime = Timezone::getCompleteTime($upgradeUnitTime);

        $query = UserQuery::qUpdateUserInfoSetCastle($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /**
     * @param bool $pending
     * @return $this
     * @throws Exception
     */
    public function setFriendArmyDeck(bool $pending = false)
    {
        list(, $buildingAttack) = BuildingServices::getArmyManpowerAndAttack($this->userId);
        $weaponAttack = WeaponServices::getAttackPower($this->userId);

        $this->friendAttack = $buildingAttack + $weaponAttack;

        $query = UserQuery::qSetFriendAttack($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /**
     * @param int $tactical
     * @param int $food
     * @param int $luxury
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function takeResources(int $tactical, int $food, int $luxury, bool $pending = false)
    {
        $this->tacticalResource = $tactical;
        $this->foodResource = $food;
        $this->luxuryResource = $luxury;

        $query = UserQuery::qAddResourcesFromUserInfo($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /**
     * @param int $manpower
     * @param bool $pending
     * @return $this
     * @throws CE
     */
    public function takeManpower(int $manpower, bool $pending = false)
    {
        $this->manpower = $manpower;

        $query = UserQuery::qAddManpowerFromUserInfo($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /*****************************************************************************************************************/
    // get user record

    /**
     * @param PDOStatement $stmt
     * @return UserDAO
     * @throws Exception
     */
    private static function getUserDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new UserDAO($res);
    }

    /**
     * @param int $userId
     * @return UserDAO
     * @throws Exception
     */
    public static function getUser(int $userId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;

        $stmt = UserQuery::jSelectUserFromAll($dao)->run();
        $user = static::getUserDAO($stmt);

        CE::check($user->isEmpty(), ErrorCode::INVALID_USER);
        return $user;
    }

    /**
     * @param int $userId
     * @return UserDAO
     * @throws Exception
     */
    public static function getUserInfo(int $userId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;

        $stmt = UserQuery::qSelectUserInfo($dao)->run();
        $user = static::getUserDAO($stmt);
        CE::check($user->isEmpty(), ErrorCode::INVALID_USER);
        return $user;
    }

    /**
     * @param string $hiveId
     * @param int $hiveUid
     * @return UserDAO
     * @throws Exception
     */
    public static function getHiveUser(string $hiveId, int $hiveUid)
    {
        $dao = new UserDAO();
        $dao->hiveId = $hiveId;
        $dao->hiveUid = $hiveUid;

        $stmt = UserQuery::qSelectHiveUser($dao)->run();
        return static::getUserDAO($stmt);
    }

    /**
     * @param int $territoryId
     * @return UserDAO
     * @throws Exception
     */
    public static function getTargetUserInfo(int $territoryId)
    {
        $dao = new UserDAO();
        $dao->territoryId = $territoryId;

        $stmt = UserQuery::qSelectUserInfoByTerritory($dao)->run();
        $user = static::getUserDAO($stmt);
        CE::check($user->isEmpty(), ErrorCode::INVALID_USER);

        return $user;
    }

    /***************************************************************/

    // create new records

    /**
     * @param array $param
     * @return int
     * @throws Exception
     */
    public static function createUserPlatform(array $param)
    {
        $dao = new UserDAO();
        $dao->userId = null;
        $dao->hiveId = $param['hiveId'];
        $dao->hiveUid = $param['hiveUid'];
        $dao->registerDate = Timezone::getNowUTC();
        $dao->country = $param['country'];
        $dao->lang = $param['lang'];
        $dao->osVersion = $param['osVersion'];
        $dao->deviceName = $param['deviceName'];
        $dao->appVersion = $param['appVersion'];

        // user_platform 테이블 레코드 추가
        $stmt = UserQuery::qInsertUserPlatform($dao)->run();
        static::resolveInsert($stmt);
        return DB::getLastInsertId();
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public static function createUserInfo(int $userId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->lastVisit = Timezone::getNowUTC();
        $dao->territoryId = null;
        $dao->name = null;
        $dao->castleLevel = 1;
        $dao->castleToLevel = 1;
        $dao->upgradeTime = Timezone::getNowUTC();
        $dao->penaltyFinishTime = null;
        $dao->autoGenerateManpower = true;
        $dao->manpower = 10;
        $dao->appendedManpower = 0;
        $dao->tacticalResource = 0;
        $dao->foodResource = 0;
        $dao->luxuryResource = 0;

        // user_info 테이블 레코드 추가
        $stmt = UserQuery::qInsertUserInfo($dao)->run();
        static::resolveInsert($stmt);
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public static function createUserStat(int $userId)
    {
        $dao = new UserDAO();
        $dao->userId = $userId;
        $dao->warRequest = 0;
        $dao->warVictory = 0;
        $dao->warDefeated = 0;
        $dao->despoilDefenseSuccess = 0;
        $dao->despoilDefenseFail = 0;
        $dao->boss1KillCount = 0;
        $dao->boss2KillCount = 0;
        $dao->boss3KillCount = 0;

        // user_statistics 테이블 레코드 추가
        $stmt = UserQuery::qInsertUserStat($dao)->run();
        static::resolveInsert($stmt);
    }
}
