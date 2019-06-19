<?php

namespace lsb\App\models;

use Exception;
use lsb\Libs\Timezone;

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
}
