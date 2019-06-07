<?php

namespace lsb\App\models;

use lsb\Libs\Timezone;
use Exception;

class UserDAO extends DAO
{
    public static $dbColumMap = [
        'userId' => 'user_id',
        'hiveId' => 'hive_id',
        'hiveUid' => 'hive_uid',
        'registerDate' => 'register_date',
        'country' => 'country',
        'lang' => 'lang',
        'osVersion' => 'os_version',
        'appVersion' => 'app_version',
        'lastVisit' => 'last_visit',
        'territoryId' => 'territory_id',
        'name' => 'name',
        'castleLevel' => 'castle_level',
        'upgradeTime' => 'upgrade_time',
        'penaltyFinishTime' => 'penalty_finish_time',
        'autoGenerateManpower' => 'auto_generate_manpower',
        'manpower' => 'manpower',
        'manpowerUsed' => 'manpower_used',
        'appendedManpower' => 'appended_manpower',
        'tacticalResource' => 'tactical_resource',
        'foodResource' => 'food_resource',
        'luxuryResource' => 'luxury_resource',
        'warRequest' => 'war_request',
        'warVictory' => 'war_victory',
        'warDefeated' => 'war_defeated',
        'despoilDefenseSuccess' => 'despoil_defense_success',
        'despoilDefenseFail' => 'despoil_defense_fail',
        'boss1KillCount' => 'boss1_kill_count',
        'boss2KillCount' => 'boss2_kill_count',
        'boss3KillCount' => 'boss3_kill_count'
    ];

    // platform
    public $userId;
    public $hiveId;
    public $hiveUid;
    public $registerDate;
    public $country;
    public $lang;
    public $osVersion;
    public $deviceName;
    public $appVersion;

    // info
    public $lastVisit;
    public $territoryId;
    public $name;
    public $castleLevel;
    public $castleToLevel;
    public $upgradeTime;
    public $penaltyFinishTime;
    public $autoGenerateManpower;
    public $manpower;
    public $manpowerUsed;
    public $appendedManpower;
    public $tacticalResource;
    public $foodResource;
    public $luxuryResource;

    // statistical
    public $warRequest;
    public $warVictory;
    public $warDefeated;
    public $despoilDefenseSuccess;
    public $despoilDefenseFail;
    public $boss1KillCount;
    public $boss2KillCount;
    public $boss3KillCount;

    // hidden property
    public $currentCastleLevel;
    public $manpowerAvailable;

    /**
     * UserDAO constructor.
     * @param array $data
     * @param bool $forUpdate
     * @throws Exception
     */
    public function __construct(array $data = [], bool $forUpdate = false)
    {
        if ($forUpdate) {
            return;
        }

        foreach ($data as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $this->{$key} = $value;
        }
        if (isset($this->upgradeTime) &&
            $this->upgradeTime <= Timezone::getNowUTC()) {
            $this->currentCastleLevel = $this->castleToLevel;
        } else {
            $this->currentCastleLevel = $this->castleLevel;
        }
        if (isset($this->manpower) &&
            isset($this->manpowerUsed)) {
            $this->manpowerAvailable = $this->manpower - $this->manpowerUsed;
        }
    }
}
