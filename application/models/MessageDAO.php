<?php

namespace lsb\App\models;

use Exception;
use lsb\Libs\Timezone;

class MessageDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'msg_id' => 'msgId',
        'user_id' => 'userId',
        'target_id' => 'targetId',
        'create_time' => 'createTime',
        'active_time' => 'activeTime'
    ];

    public $msgId;
    public $userId;
    public $targetId;
    public $createTime;
    public $activeTime;

    // hidden property
    public $target;

    /**
     * MessageDAO constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data, self::$dbColumToPropertyMap);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isActive()
    {
        return isset($this->activeTime) && $this->activeTime <= Timezone::getNowUTC();
    }
}
