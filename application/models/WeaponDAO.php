<?php

namespace lsb\App\models;

use lsb\App\query\WeaponQuery;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;
use lsb\Libs\CtxException as CE;

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
    public function isCreated()
    {
        return isset($this->createTime) && $this->createTime <= Timezone::getNowUTC();
    }

    /*****************************************************************************************************************/
    // set weapon

    public static function container(int $weaponId)
    {
        $weapon = new WeaponDAO();
        $weapon->weaponId = $weaponId;
        return $weapon;
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

        $query = WeaponQuery::qSetUpgradeFromWeapon($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /*****************************************************************************************************************/
    // get weapon record

    /**
     * @param PDOStatement $stmt
     * @return WeaponDAO
     * @throws Exception
     */
    private static function getWeaponDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new WeaponDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return WeaponDAO[]
     * @throws Exception
     */
    private static function getWeaponDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new WeaponDAO($row);
        }
        return $res;
    }

    /**
     * @param int $weaponId
     * @return WeaponDAO
     * @throws Exception
     */
    public static function getWeapon(int $weaponId)
    {
        $dao = new WeaponDAO();
        $dao->weaponId = $weaponId;

        $stmt = WeaponQuery::qSelectWeapon($dao)->run();
        $weapon = static::getWeaponDAO($stmt);
        CE::check($weapon->isEmpty(), ErrorCode::INVALID_WEAPON);
        return $weapon;
    }

    /**
     * @param int $userId
     * @return WeaponDAO[]
     * @throws Exception
     */
    public static function getWeapons(int $userId)
    {
        $dao = new WeaponDAO();
        $dao->userId = $userId;

        $stmt = WeaponQuery::qSelectWeaponsByUser($dao)->run();
        return static::getWeaponDAOs($stmt);
    }

    /*****************************************************************************************************************/
    // create weapon record

    /**
     * @param int $userId
     * @param int $weaponType
     * @param int $createUnitTime
     * @return int
     * @throws Exception
     */
    public static function createWeapon(int $userId, int $weaponType, int $createUnitTime)
    {
        $dao = new WeaponDAO();
        $dao->weaponId = null;
        $dao->userId = $userId;
        $dao->weaponType = $weaponType;
        $dao->createTime = Timezone::getCompleteTime($createUnitTime);
        $dao->upgradeTime = null;
        $dao->level = 1;
        $dao->toLevel = 1;
        $dao->lastUpdate = Timezone::getNowUTC();

        $stmt = WeaponQuery::qInsertWeapon($dao)->run();
        $errorCode = static::resolveInsert($stmt);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::ALREADY_CREATED_WEAPON);

        return DB::getLastInsertId();
    }
}
