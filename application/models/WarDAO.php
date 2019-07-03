<?php

namespace lsb\App\models;

use lsb\App\query\WarQuery;
use lsb\Libs\CtxException as CE;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;

class WarDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'war_id' => 'warId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
        'attack' => 'attack',
        'friend_attack' => 'friendAttack',
        'manpower' => 'manpower',
        'food_resource' => 'foodResource',
        'target_defense' => 'targetDefense',
        'prepare_time' => 'prepareTime',
        'finish_time' => 'finishTime'
    ];

    private static $propertyToDBColumnMap = [];
    public static function getColumnMap()
    {
        if (empty(self::$propertyToDBColumnMap)) {
            self::$propertyToDBColumnMap = array_flip(self::$dbColumToPropertyMap);
        }
        return self::$propertyToDBColumnMap;
    }

    public $warId;
    public $userId;
    public $territoryId;
    public $attack;
    public $friendAttack;
    public $manpower;
    public $foodResource;
    public $targetDefense;
    public $prepareTime;
    public $finishTime;

    public function __construct(array $data = [])
    {
        if (count($data) === 0) {
            return;
        }
        parent::__construct($data, self::$dbColumToPropertyMap);
    }

    /*****************************************************************************************************************/
    // check function

    /**
     * @return bool
     * @throws Exception
     */
    public function isFinished()
    {
        return isset($this->finishTime) && $this->finishTime <= Timezone::getNowUTC();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isPrepared()
    {
        return isset($this->prepareTime) && $this->prepareTime <= Timezone::getNowUTC();
    }

    /**
     * @throws CtxException
     */
    public function resolveWarResult()
    {
        $attack = $this->attack;
        $defense = $this->targetDefense;
        $ratio = $attack - $defense / $attack;

        if ($ratio <= 0) { // 패배, 무승부
            return [];
        }

        $manpower = $this->manpower;
        $food = $this->foodResource;
        list(, , , $defaultManpowerRatio) = Plan::getUnitWar();

        $manpower -= (int)($manpower * $defaultManpowerRatio);
        $remainManpower = $manpower * $ratio;
        $remainFood = $food * $ratio;

        return [$remainManpower, $remainFood];
    }

    /*****************************************************************************************************************/
    // set war

    /**
     * @return $this
     * @throws CtxException
     */
    public function removeWar()
    {
        $stmt = WarQuery::qDeleteWar($this)->run();
        $this->resolveDelete($stmt);
        return $this;
    }

    /*****************************************************************************************************************/
    // get war record

    /**
     * @param PDOStatement $stmt
     * @return WarDAO
     * @throws Exception
     */
    private static function getWarDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new WarDAO($res);
    }

    /**
     * @param int $warId
     * @return WarDAO
     * @throws Exception
     */
    public static function getWar(int $warId)
    {
        $dao = new WarDAO();
        $dao->warId = $warId;

        $stmt = WarQuery::qSelectWar($dao)->run();
        $war = static::getWarDAO($stmt);
        CE::check($war->isEmpty(), ErrorCode::INVALID_WAR);
        return $war;
    }

    /**
     * @param int $userId
     * @return WarDAO
     * @throws Exception
     */
    public static function getWars(int $userId)
    {
        $dao = new WarDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = WarQuery::qSelectWarByUser($dao)->run();
        return static::getWarDAO($stmt);
    }

    /**
     * @param int $userId
     * @return WarDAO|void
     * @throws Exception
     */
    public static function getFinishedWar(int $userId)
    {
        $dao = new WarDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = WarQuery::qSelcetFinishedWarByUser($dao)->run();
        return static::getWarDAO($stmt);
    }

    /*****************************************************************************************************************/
    // create new record

    /**
     * @param array $param
     * @return int
     * @throws CtxException
     */
    public static function createWar(array $param)
    {
        $dao = new WarDAO();
        $dao->warId = null;
        $dao->userId = $param['userId'];
        $dao->territoryId = $param['territoryId'];
        $dao->attack = $param['attack'];
        $dao->friendAttack = $param['friendAttack'];
        $dao->manpower = $param['manpower'];
        $dao->foodResource = $param['foodResource'];
        $dao->targetDefense = $param['targetDefense'];
        $dao->prepareTime = Timezone::getCompleteTime($param['prepareUnitTime']);
        $dao->finishTime = Timezone::getCompleteTime($param['finishUnitTime']);

        $stmt = WarQuery::qInsertWar($dao)
            ->checkError([DB::DUPLICATE_ERRORCODE])
            ->run();
        $errorCode = static::resolveInsert($stmt);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::ALREADY_WARRING);

        return DB::getLastInsertId();
    }
}
