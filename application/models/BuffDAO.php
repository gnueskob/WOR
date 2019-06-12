<?php

namespace lsb\App\models;

class BuffDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'buff_id' => 'weaponId',
        'user_id' => 'userId',
        'buff_type' => 'buffType',
        'finish_time' => 'finishTime'
    ];

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
}
