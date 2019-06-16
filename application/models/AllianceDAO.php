<?php

namespace lsb\App\models;

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
}