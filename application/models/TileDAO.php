<?php

namespace lsb\App\models;

use lsb\App\query\TileQuery;
use lsb\Libs\CtxException as CE;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;

class TileDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'explore_id' => 'exploreId',
        'user_id' => 'userId',
        'tile_id' => 'tileId',
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
    public $tileId;
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
    // set tile explore

    public static function container(int $exploreId)
    {
        $tile = new TileDAO();
        $tile->exploreId = $exploreId;
        return $tile;
    }

    /*****************************************************************************************************************/
    // get tile explore record

    /**
     * @param PDOStatement $stmt
     * @return TileDAO
     * @throws Exception
     */
    private static function getTileDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new TileDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return TileDAO[]
     * @throws Exception
     */
    private static function getTileDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new TileDAO($row);
        }
        return $res;
    }

    /**
     * @param int $exploreId
     * @return TileDAO
     * @throws Exception
     */
    public static function getTile(int $exploreId)
    {
        $dao = new TileDAO();
        $dao->exploreId = $exploreId;

        $stmt = TileQuery::qSelectTile($dao)->run();
        $tile = static::getTileDAO($stmt);
        CE::check($tile->isEmpty(), ErrorCode::INVALID_EXPLORE);
        return $tile;
    }

    /**
     * @param int $userId
     * @return TileDAO[]
     * @throws Exception|Exception
     */
    public static function getTiles(int $userId)
    {
        $dao = new TileDAO();
        $dao->userId = $userId;

        $stmt = TileQuery::qSelectTileByUser($dao)->run();
        return static::getTileDAOs($stmt);
    }

    /**
     * @param int $userId
     * @param int $tileId
     * @return TileDAO
     * @throws Exception
     */
    public static function getSpecificTile(int $userId, int $tileId)
    {
        $dao = new TileDAO();
        $dao->userId = $userId;
        $dao->tileId = $tileId;

        $stmt = TileQuery::qSelectTileByUserAndTileId($dao)->run();
        $tile = static::getTileDAO($stmt);

        return $tile;
    }

    /*****************************************************************************************************************/
    // create new record

    /**
     * @param int $userId
     * @param int $tileId
     * @param int $exploreUnitTime
     * @return int
     * @throws CE
     */
    public static function exploreTile(int $userId, int $tileId, int $exploreUnitTime)
    {
        $dao = new TileDAO();
        $dao->exploreId = null;
        $dao->userId = $userId;
        $dao->tileId = $tileId;
        $dao->exploreTime = Timezone::getCompleteTime($exploreUnitTime);

        // 타일 탐사 데이터 추가
        $stmt = TileQuery::qInsertTile($dao)->run();
        $errorCode = static::resolveInsert($stmt);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::ALREADY_EXPLORED);

        return DB::getLastInsertId();
    }
}
