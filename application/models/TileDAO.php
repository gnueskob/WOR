<?php

namespace lsb\App\models;

class TileDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'explore_id' => 'exploreId',
        'user_id' => 'userId',
        'tile_id' => 'tileId',
        'explore_time' => 'exploreTime'
    ];

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
}
