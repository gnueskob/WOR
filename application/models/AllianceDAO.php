<?php

namespace lsb\App\models;

use Exception;
use lsb\App\query\AllianceQuery;
use lsb\Libs\CtxException as CE;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\Timezone;
use PDOStatement;

class AllianceDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'alliance_id' => 'allianceId',
        'user_id' => 'userId',
        'friend_id' => 'friendId',
        'created_time' => 'createdTime'
    ];

    private static $propertyToDBColumnMap = [];
    public static function getColumnMap()
    {
        if (empty(self::$propertyToDBColumnMap)) {
            self::$propertyToDBColumnMap = array_flip(self::$dbColumToPropertyMap);
        }
        return self::$propertyToDBColumnMap;
    }

    public $allianceId;
    public $userId;
    public $friendId;
    public $createdTime;

    public function __construct(array $data = [])
    {
        if (count($data) === 0) {
            return;
        }
        parent::__construct($data, self::$dbColumToPropertyMap);
    }

    /*****************************************************************************************************************/
    // set alliance

    public static function container(int $allianceId = 0)
    {
        $alliance = new AllianceDAO();
        $alliance->allianceId = $allianceId;
        return $alliance;
    }

    /**
     * @return $this
     * @throws CtxException
     */
    public function resolveAccept()
    {
        $stmt = AllianceQuery::qDeleteAllianceWait($this)->run();
        $this->resolveDelete($stmt);
        return $this;
    }

    /**
     * @return $this
     * @throws CtxException
     */
    public function cancel()
    {
        $stmt = AllianceQuery::qDeleteAlliance($this)->run();
        $this->resolveDelete($stmt);
        return $this;
    }

    /**
     * @return $this
     * @throws CtxException
     */
    public function reject()
    {
        $stmt = AllianceQuery::qDeleteAllianceWait($dao)->run();
        $this->resolveDelete($stmt);
        return $this;
    }

    /*****************************************************************************************************************/
    // get alliance record

    /**
     * @param PDOStatement $stmt
     * @return AllianceDAO
     * @throws Exception
     */
    private static function getAllianceDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new AllianceDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return AllianceDAO[]
     * @throws Exception
     */
    private static function getAllianceDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new AllianceDAO($row);
        }
        return $res;
    }

    /**
     * @param int $allianceId
     * @return AllianceDAO
     * @throws Exception
     */
    public static function getAlliance(int $allianceId)
    {
        $dao = new AllianceDAO();
        $dao->allianceId = $allianceId;

        $stmt = AllianceQuery::qSelectAlliance($dao)->run();
        $alliance = static::getAllianceDAO($stmt);
        CE::check($alliance->isEmpty(), ErrorCode::INVALID_ALLIANCE);

        return $alliance;
    }

    /**
     * @param int $allianceWaitId
     * @return AllianceDAO
     * @throws Exception
     */
    public static function getAllianceWait(int $allianceWaitId)
    {
        $dao = new AllianceDAO();
        $dao->allianceId = $allianceWaitId;

        $stmt = AllianceQuery::qSelectAllianceWait($dao)->run();
        $alliance = static::getAllianceDAO($stmt);
        CE::check($alliance->isEmpty(), ErrorCode::INVALID_ALLIANCE);

        return $alliance;
    }

    /**
     * @param int $userId
     * @return AllianceDAO[]
     * @throws Exception
     */
    public static function getAcceptedAlliance(int $userId)
    {
        $dao = new AllianceDAO();
        $dao->userId = $userId;

        $stmt = AllianceQuery::qSelectAcceptedAlliances($dao)->run();
        return static::getAllianceDAOs($stmt);
    }

    /**
     * @param int $userId
     * @return AllianceDAO[]
     * @throws Exception
     */
    public static function getWatingAlliance(int $userId)
    {
        $dao = new AllianceDAO();
        $dao->userId = $userId;

        $stmt = AllianceQuery::qSelectWatingAlliances($dao)->run();
        return static::getAllianceDAOs($stmt);
    }

    /**
     * @param int $userId
     * @param int $friendId
     * @return AllianceDAO
     * @throws Exception
     */
    public static function getAllianceFriend(int $userId, int $friendId)
    {
        $dao = new AllianceDAO();
        $dao->userId = $userId;
        $dao->friendId = $friendId;

        $stmt = AllianceQuery::qSelectAllianceByUserAndFriend($dao)->run();
        return static::getAllianceDAO($stmt);
    }

    /*****************************************************************************************************************/
    // create new record

    /**
     * @param int $userId
     * @param int $friendId
     * @return int
     * @throws Exception
     */
    public static function requestAlliance(int $userId, int $friendId)
    {
        $dao = new AllianceDAO();
        $dao->allianceId = null;
        $dao->userId = $userId;
        $dao->friendId = $friendId;
        $dao->createdTime = Timezone::getNowUTC();

        $stmt = AllianceQuery::qInsertAllianceWait($dao)
            ->checkError([DB::DUPLICATE_ERRORCODE])
            ->run();
        $errorCode = static::resolveInsert($stmt);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::ALREADY_REQUESTED);

        return DB::getLastInsertId();
    }

    /**
     * @param int $userId
     * @param int $friendId
     * @return int
     * @throws Exception
     */
    public static function acceptAlliance(int $userId, int $friendId)
    {
        $dao = new AllianceDAO();
        $dao->allianceId = null;
        $dao->userId = $userId;
        $dao->friendId = $friendId;
        $dao->createdTime = Timezone::getNowUTC();

        $stmt = AllianceQuery::qInsertAlliance($dao)
            ->checkError([DB::DUPLICATE_ERRORCODE])
            ->run();
        $errorCode = static::resolveInsert($stmt);
        CE::check($errorCode === DB::DUPLICATE_ERRORCODE, ErrorCode::ALREADY_REQUESTED);

        return DB::getLastInsertId();
    }
}
