<?php

namespace lsb\App\query;

class map
{
    private static $propertyToDBColumnMap = [];
    private static $dbColumToPropertyMap = [
        'user_id' => 'userId',
        'hive_id' => 'hiveId',
        'hive_uid' => 'hiveUid',
        'register_date' => 'registerDate',
        'country' => 'country',
        'lang' => 'lang',
        'os_version' => 'osVersion',
        'app_version' => 'appVersion',
        'lastVisit' => 'last_visit',
        'territory_id' => 'territoryId',
        'name' => 'name',
        'castle_level' => 'castleLevel',
        'upgrade_time' => 'upgradeTime',
        'penalty_finish_time' => 'penaltyFinishTime',
        'auto_generate_manpower' => 'autoGenerateManpower',
        'manpower' => 'manpower',
        'manpower_used' => 'manpowerUsed',
        'appended_manpower' => 'appendedManpower',
        'tactical_resource' => 'tacticalResource',
        'food_resource' => 'foodResource',
        'luxury_resource' => 'luxuryResource',
        'war_request' => 'warRequest',
        'war_victory' => 'warVictory',
        'war_defeated' => 'warDefeated',
        'despoil_defense_success' => 'despoilDefenseSuccess',
        'despoil_defense_fail' => 'despoilDefenseFail',
        'boss1_kill_count' => 'boss1KillCount',
        'boss2_kill_count' => 'boss2KillCount',
        'boss3_kill_count' => 'boss3KillCount'
    ];
}
