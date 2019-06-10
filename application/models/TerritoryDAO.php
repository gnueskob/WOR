<?php

namespace lsb\App\models;

class TerritoryDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'explore_id' => 'exploreId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
        'explore_time' => 'exploreTime'
    ];

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
}
