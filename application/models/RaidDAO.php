<?php

namespace lsb\App\models;

use Exception;
use lsb\App\query\RaidQuery;
use lsb\Libs\CtxException as CE;
use lsb\Libs\ErrorCode;
use lsb\Libs\Timezone;
use PDOStatement;

class RaidDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'raid_id' => 'raidId',
        'boss_id' => 'bossId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
        'boss_type' => 'bossType',
        'is_victory' => 'isVictory',
        'finish_time' => 'finishTime',
    ];

    private static $propertyToDBColumnMap = [];
    public static function getColumnMap()
    {
        if (empty(self::$propertyToDBColumnMap)) {
            self::$propertyToDBColumnMap = array_flip(self::$dbColumToPropertyMap);
        }
        return self::$propertyToDBColumnMap;
    }

    public $raidId;
    public $bossId;
    public $userId;
    public $territoryId;
    public $bossType;
    public $isVictory;
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
    // set raid

    /*****************************************************************************************************************/
    // get raid record

    /**
     * @param PDOStatement $stmt
     * @return RaidDAO
     * @throws Exception
     */
    private static function getRaidDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new RaidDAO($res);
    }

    /**
     * @param int $raidId
     * @return RaidDAO
     * @throws Exception
     */
    public static function getRaid(int $raidId)
    {
        $dao = new RaidDAO();
        $dao->raidId = $raidId;

        $stmt = RaidQuery::qSelectRaid($dao)->run();
        $raid = static::getRaidDAO($stmt);
        CE::check($raid->isEmpty(), ErrorCode::INVALID_RAID);

        return $raid;
    }

    /**
     * @param int $userId
     * @return RaidDAO
     * @throws Exception
     */
    public static function getRaids(int $userId)
    {
        $dao = new RaidDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = RaidQuery::qSelectRaidByUser($dao)->run();
        return static::getRaidDAO($stmt);
    }

    /**
     * @param int $userId
     * @return RaidDAO
     * @throws Exception
     */
    public static function getFinishedRaid(int $userId)
    {
        $dao = new RaidDAO();
        $dao->userId = $userId;
        $dao->finishTime = Timezone::getNowUTC();

        $stmt = RaidQuery::qSelcetFinishedRaidByUser($dao)->run();
        return static::getRaidDAO($stmt);
    }

    /*****************************************************************************************************************/
    // create raid record
}
