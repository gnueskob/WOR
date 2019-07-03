<?php

namespace lsb\App\models;

use Exception;
use lsb\App\query\RaidQuery;
use lsb\Libs\CtxException as CE;
use lsb\Libs\Timezone;
use PDOStatement;

class BossDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'boss_id' => 'bossId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
        'hit_point' => 'hitPoint',
        'boss_type' => 'bossType',
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

    public $bossId;
    public $userId;
    public $territoryId;
    public $hitPoint;
    public $bossType;
    public $finishTime;

    public function __construct(array $data = [])
    {
        if (count($data) === 0) {
            return;
        }
        parent::__construct($data, self::$dbColumToPropertyMap);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isFinished()
    {
        return isset($this->finishTime) && $this->finishTime < Timezone::getNowUTC();
    }

    /*****************************************************************************************************************/
    // get boss record

    /**
     * @param PDOStatement $stmt
     * @return BossDAO
     * @throws Exception
     */
    private static function getBossDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new BossDAO($res);
    }

    /**
     * @param int $territoryId
     * @return BossDAO
     * @throws Exception
     */
    public static function getBossInTerritory(int $territoryId)
    {
        $dao = new BossDAO();
        $dao->territoryId = $territoryId;

        $stmt = RaidQuery::qSelectBossByTerritory($dao)->run();
        $boss = static::getBossDAO($stmt);
        return $boss;
    }
}
