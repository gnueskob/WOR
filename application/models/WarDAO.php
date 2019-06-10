<?php

namespace lsb\App\models;

class WarDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'war_id' => 'warId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
        'attack' => 'attack',
        'manpower' => 'manpower',
        'food_resource' => 'foodResource',
        'finish_time' => 'finishTime'
    ];

    public $warId;
    public $userId;
    public $territoryId;
    public $attack;
    public $manpower;
    public $foodResource;
    public $finishTime;

    public function __construct(array $data = [])
    {
        if (count($data) === 0) {
            return;
        }
        parent::__construct($data, self::$dbColumToPropertyMap);
    }
}
