<?php

namespace lsb\App\models;

use lsb\Libs\Timezone;
use Exception;

class WeaponDAO extends DAO
{
    private static $dbColumToPropertyMap = [
        'weapon_id' => 'weaponId',
        'user_id' => 'userId',
        'weapon_type' => 'weaponType',
        'create_time' => 'createTime',
        'upgrade_time' => 'upgradeTime',
        'level' => 'level',
        'toLevel' => 'to_level',
        'last_update' => 'lastUpdate'
    ];

    public $weaponId;
    public $userId;
    public $weaponType;

    public $createTime;
    public $upgradeTime;
    public $level;
    public $toLevel;
    public $lastUpdate;

    // hidden property
    public $currentLevel;

    /**
     * WeaponDAO constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data = [])
    {
        if (count($data) === 0) {
            return;
        }

        parent::__construct($data, self::$dbColumToPropertyMap);
        if (isset($this->upgradeTime) &&
            $this->upgradeTime <= Timezone::getNowUTC()) {
            $this->currentLevel = $this->toLevel;
        } else {
            $this->currentLevel = $this->level;
        }
    }
}
