<?php

namespace lsb\App\models;

use Exception;
use lsb\App\query\TerritoryQuery;
use lsb\Libs\CtxException as CE;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\Timezone;
use PDOStatement;

class TerritoryDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'explore_id' => 'exploreId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
        'explore_time' => 'exploreTime'
    ];

    private static $propertyToDBColumnMap = [];

    public static function getColumnMap()
    {
        if (empty(self::$propertyToDBColumnMap)) {
            self::$propertyToDBColumnMap = array_flip(self::$dbColumToPropertyMap);
        }
        return self::$propertyToDBColumnMap;
    }

    public $exploreId;
    public $userId;
    public $territoryId;
    public $exploreTime;

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
    public function isExplored()
    {
        return isset($this->exploreTime) && $this->exploreTime <= Timezone::getNowUTC();
    }

    /*****************************************************************************************************************/
    // set territory explore

    /**
     * @param bool $pending
     * @return $this
     * @throws Exception
     */
    public function finishExplore(bool $pending = false)
    {
        $this->exploreTime = Timezone::getNowUTC();

        $query = TerritoryQuery::qSetExploreTime($this);
        $this->resolveUpdate($query, $pending);
        return $this;
    }

    /*****************************************************************************************************************/
    // get territory explore record

    /**
     * @param PDOStatement $stmt
     * @return TerritoryDAO
     * @throws Exception
     */
    private static function getTerritoryDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new TerritoryDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return TerritoryDAO[]
     * @throws Exception
     */
    private static function getTerritoriesDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new TerritoryDAO($row);
        }
        return $res;
    }

    /**
     * @param int $exploreId
     * @return TerritoryDAO
     * @throws Exception
     */
    public static function getTerritory(int $exploreId)
    {
        $dao = new TerritoryDAO();
        $dao->exploreId = $exploreId;

        $stmt = TerritoryQuery::qSelectTerritory($dao)->run();
        $territory = static::getTerritoryDAO($stmt);
        CE::check($territory->isEmpty(), ErrorCode::INVALID_EXPLORE);
        return $territory;
    }

    /**
     * @param int $userId
     * @return TerritoryDAO[]
     * @throws Exception
     */
    public static function getTerritories(int $userId)
    {
        $dao = new TerritoryDAO();
        $dao->userId = $userId;

        $stmt = TerritoryQuery::qSelectTerritoryByUser($dao)->run();
        return static::getTerritoriesDAOs($stmt);
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @return TerritoryDAO
     * @throws Exception
     */
    public static function getSpecificTerritory(int $userId, int $territoryId)
    {
        $dao = new TerritoryDAO();
        $dao->userId = $userId;
        $dao->territoryId = $territoryId;

        $stmt = TerritoryQuery::qSelectTerritoryByUserAndTerritoryId($dao)->run();
        $territory = static::getTerritoryDAO($stmt);

        return $territory;
    }

    /**
     * @param int $userId
     * @return mixed
     * @throws Exception
     */
    public static function getCurrentExploringTerritoryNumber(int $userId)
    {
        $dao = new TerritoryDAO();
        $dao->userId = $userId;
        $dao->exploreTime = Timezone::getNowUTC();

        $countFetchName = "CNT";

        $stmt = TerritoryQuery::qCountExploringTerritoryByUser($dao, $countFetchName)->run();
        return $stmt->fetch()[$countFetchName];
    }

    /*****************************************************************************************************************/
    // create territory record

    /**
     * @param int $userId
     * @param int $territoryId
     * @param int $exploreUnitTime
     * @return int
     * @throws CE
     */
    public static function exploreTerritory(int $userId, int $territoryId, int $exploreUnitTime)
    {
        $dao = new TerritoryDAO();
        $dao->exploreId = null;
        $dao->userId = $userId;
        $dao->territoryId = $territoryId;
        $dao->exploreTime = Timezone::getCompleteTime($exploreUnitTime);

        // 영토 탐사 데이터 추가
        $stmt = TerritoryQuery::qInsertTerritory($dao)
            ->checkError([DB::DUPLICATE_ERRORCODE])
            ->run();
        $errorCode = static::resolveInsert($stmt);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::ALREADY_EXPLORED);
        return DB::getLastInsertId();
    }
}
