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
        'building_list' => 'buildingList',
        'food_resource' => 'foodResource',
        'target_defense' => 'targetDefense',
        'prepare_time' => 'prepareTime',
        'finish_time' => 'finishTime'
    ];

    public $warId;
    public $userId;
    public $territoryId;
    public $attack;
    public $manpower;
    public $buildingList;
    public $foodResource;
    public $targetDefense;
    public $prepareTime;
    public $finishTime;

    public function __construct(array $data = [])
    {
        if (count($data) === 0) {
            return;
        }
        parent::__construct($data, self::$dbColumToPropertyMap);
    }
}
