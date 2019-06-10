<?php

namespace lsb\App\models;

class BufDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'buf_id' => 'weaponId',
        'user_id' => 'userId',
        'buf_type' => 'bufType',
        'finish_time' => 'finishTime'
    ];

    public $bufId;
    public $userId;
    public $bufType;
    public $finishTime;

    public function __construct(array $data = [])
    {
        if (count($data) === 0) {
            return;
        }

        parent::__construct($data, self::$dbColumToPropertyMap);
    }
}
