<?php

namespace lsb\App\models;

use lsb\Libs\Timezone;
use Exception;

class WarDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'war_id' => 'warId',
        'user_id' => 'userId',
        'territory_id' => 'territoryId',
        'attack' => 'attack',
        'manpower' => 'manpower',
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

    /**
     * @return bool
     * @throws Exception
     */
    public function isFinished()
    {
        return isset($this->finishTime) && $this->finishTime <= Timezone::getNowUTC();
    }
}
