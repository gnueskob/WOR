<?php

namespace lsb\App\models;

use Exception;
use lsb\App\query\BuffQuery;
use lsb\Libs\CtxException as CE;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\Timezone;
use PDOStatement;

class BuffDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'buff_id' => 'weaponId',
        'user_id' => 'userId',
        'buff_type' => 'buffType',
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

    public $buffId;
    public $userId;
    public $buffType;
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
        return isset($this->finishTime) && $this->finishTime < Timezone::getNowUTC();
    }

    /*****************************************************************************************************************/
    // set buff

    public static function container(int $buffId = 0)
    {
        $buff = new BuffDAO();
        $buff->buffId = $buffId;
        return $buff;
    }

    /**
     * @param int $userId
     * @return $this
     * @throws CE
     */
    public function resolveExpiredBuff(int $userId)
    {
        $this->userId = $userId;

        $stmt = BuffQuery::qDeleteExpiredBuff($this)->run();
        $this->resolveDelete($stmt);
        return $this;
    }

    /*****************************************************************************************************************/
    // get buff record

    /**
     * @param PDOStatement $stmt
     * @return BuffDAO
     * @throws Exception
     */
    public static function getBuffDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new BuffDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return BuffDAO[]
     * @throws Exception
     */
    public static function getBuffDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new BuffDAO($row);
        }
        return $res;
    }

    /**
     * @param int $buffId
     * @return BuffDAO
     * @throws Exception
     */
    public static function getBuff(int $buffId)
    {
        $dao = new BuffDAO();
        $dao->buffId = $buffId;

        $stmt = BuffQuery::qSelectBuff($dao)->run();
        $buff = static::getBuffDAO($stmt);
        CE::check($buff->isEmpty(), ErrorCode::INVALID_BUFF);
        return $buff;
    }

    /**
     * @param int $userId
     * @return BuffDAO[]
     * @throws Exception
     */
    public static function getBuffs(int $userId)
    {
        $dao = new BuffDAO();
        $dao->userId = $userId;

        $stmt = BuffQuery::qSelectBuffByUser($dao)->run();
        return static::getBuffDAOs($stmt);
    }

    /*****************************************************************************************************************/
    // create new buff record

    /**
     * @param int $userId
     * @param int $buffType
     * @param int $finishUnitTime
     * @return int
     * @throws CtxException
     */
    public static function createBuff(int $userId, int $buffType, int $finishUnitTime)
    {
        $dao = new BuffDAO();
        $dao->buffId = null;
        $dao->userId = $userId;
        $dao->buffType = $buffType;
        $dao->finishTime = Timezone::getCompleteTime($finishUnitTime);

        $stmt = BuffQuery::qInsertBuff($dao)
            ->checkError([DB::DUPLICATE_ERRORCODE])
            ->run();
        $errorCode = static::resolveInsert($stmt);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::ALREADY_HAS_BUFF);

        return DB::getLastInsertId();
    }
}
