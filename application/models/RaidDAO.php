<?php

namespace lsb\App\models;

use Exception;
use lsb\Libs\Timezone;

class RaidDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'raid_id' => 'raidId',
        'boss_id' => 'bossId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
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
    public $isVictory;
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