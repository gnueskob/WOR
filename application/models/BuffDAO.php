<?php

namespace lsb\App\models;

use Exception;
use lsb\Libs\Timezone;

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

    /**
     * @return bool
     * @throws Exception
     */
    public function isFinished()
    {
        return isset($this->finishTime) && $this->finishTime < Timezone::getNowUTC();
    }
}
