<?php

namespace lsb\App\query;

use lsb\App\models\UserDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;

class UserQuery
{
    public static function selectUser(UserDAO $user)
    {
        $q = "
            SELECT up.*, ui.*, us.*
            FROM user_platform up 
                JOIN user_info ui ON up.user_id = ui.user_id
                JOIN user_statistics us ON up.user_id = us.user_id
            WHERE up.user_id = :user_id;
        ";
        $p = [':user_id' => $user->userId];
        return DB::runQuery($q, $p);
    }

    public static function selectUserPlatformByHive(UserDAO $user)
    {
        $q = "
            SELECT user_id
            FROM user_platform
            WHERE hive_id = :hive_id
              AND hive_uid = :hive_uid;
        ";
        $p = [
            ':hive_id' => $user->hiveId,
            ':hive_uid' => $user->hiveUid
        ];
        return DB::runQuery($q, $p);
    }

    public static function selectUserInfo(UserDAO $user)
    {
        $q = "
            SELECT *
            FROM user_info
            WHERE user_id = :user_id;
        ";
        $p = ['user_id' => $user->userId];
        return DB::runQuery($q, $p);
    }

    /* not used
    public static function selectUserCastleUpgrade(array $d)
    {
        $q = "
            SELECT *
            FROM user_castle_upgrade
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $d['user_id']];
        return DB::runQuery($q, $p);
    }*/

    public static function updateUserInfoWithLastVisit(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET last_visit = :last_visit
            WHERE user_id = :user_id;
        ";
        $p = [
            ':name' => $user->name,
            ':last_visit' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithName(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET name = :name
            WHERE user_id = :user_id;
        ";
        $p = [
            ':name' => $user->name,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithTerritory(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET territory_id = :territory_id
            WHERE user_id = :user_id;
        ";
        $p = [
            ':territory_id' => $user->territoryId,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithResource(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET tactical_resource = :tactical_resource,
                food_resource = :food_resource,
                luxury_resource = :luxury_resource
            WHERE user_id = :user_id;
        ";
        $p = [
            ':tactical_resource' => $user->tacticalResource,
            ':food_resource' => $user->foodResource,
            ':luxury_resource' => $user->luxuryResource,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithPenaltyTime(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET penalty_finish_time = :penalty_finish_time
            WHERE user_id = :user_id;
        ";
        $p = [
            ':penalty_finish_time' => $user->penaltyFinishTime,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithUpgradeAndResource(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET tactical_resource = :tactical_resource,
                food_resource = :food_resource,
                luxury_resource = :luxury_resource,
                castle_level = :castle_level,
                castle_to_level = :castle_to_level,
                to_level = :to_level
            WHERE user_id = :user_id;
        ";
        $p = [
            ':tactical_resource' => $user->tacticalResource,
            ':food_resource' => $user->foodResource,
            ':luxury_resource' => $user->luxuryResource,
            ':castle_level' => $user->castleLevel,
            ':castle_to_level' => $user->castleToLevel,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithUsedManpower(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET manpower_used = manpower_used + :manpower_used
            WHERE user_id = :user_id;
        ";
        $p = [
            ':manpower_used' => $user->manpowerUsed,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param UserDAO $user
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertUserPlatform(UserDAO $user)
    {
        $q = "
            INSERT INTO user_platform
            VALUE (
                    :user_id,
                    :hive_id,
                    :hive_uid,
                    :register_date,
                    :country,
                    :lang,
                    :os_version,
                    :device_name,
                    :app_version
            );
        ";
        $p = [
            ':user_id' => null,
            ':hive_id' => $user->hiveId,
            ':hive_uid' => $user->hiveUid,
            ':register_date' => Timezone::getNowUTC(),
            ':country' => $user->country,
            ':lang' => $user->lang,
            ':os_version' => $user->osVersion,
            ':device_name' => $user->deviceName,
            ':app_version' => $user->appVersion
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param UserDAO $user
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertUserInfo(UserDAO $user)
    {
        $q = "
            INSERT INTO user_info
            VALUE (
                    :user_id,
                    :last_update,
                    :territory_id,
                    :name,
                    :castle_level,
                    :castle_to_level,
                    :upgrade_time,
                    :penalty_finish_time,
                    :auto_generate_manpower,
                    :manpower,
                    :appended_manpower,
                    :tactical_resource,
                    :food_resource,
                    :luxury_resource
            );
        ";
        // TODO: 초기값 기획 데이터 변환
        $p = [
            ':user_id' => $user->userId,
            ':last_update' => Timezone::getNowUTC(),
            ':territory_id' => 0,
            ':name' => null,
            ':castle_level' => 1,
            ':castle_to_level' => 1,
            ':upgrade_time' => null,
            ':penalty_finish_time' => null,
            ':auto_generate_manpower' => true,
            ':manpower' => 10,
            ':appended_manpower' => 0,
            ':tactical_resource' => 0,
            ':food_resource' => 0,
            ':luxury_resource' => 0
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertUserStatistics(UserDAO $user)
    {
        $q = "
            INSERT INTO user_statistic
            VALUE (
                    :user_id,
                    :war_request,
                    :war_victory,
                    :war_defeated,
                    :despoil_defense_success,
                    :despoil_defense_fail,
                    :boss1_kill_count,
                    :boss2_kill_count,
                    :boss3_kill_count
            );
        ";
        // TODO: 초기값 기획 데이터 변환
        $p = [
            ':user_id' => $user->userId,
            ':war_request' => 0,
            ':war_victory' => 0,
            ':war_defeated' => 0,
            ':despoil_defense_success' => 0,
            ':despoil_defense_fail' => 0,
            ':boss1_kill_count' => 0,
            ':boss2_kill_count' => 0,
            ':boss3_kill_count' => 0
        ];
        return DB::runQuery($q, $p);
    }

    /* not used
    public static function insertUserCastleUpgrade(array $d)
    {
        $q = "
            INSERT INTO user_castle_upgrade
            VALUE (
                   :id,
                   :user_id,
                   :from_level,
                   :to_level,
                   :upgrade_finish_time
            );
        ";
        $p = [
            ':id' => null,
            ':user_id' => $d['user_id'],
            ':from_level' => $d['from_level'],
            ':to_level' => $d['to_level'],
            ':upgrade_finish_time' => $d['upgrade_finish_time']
        ];
        return DB::runQuery($q, $p);
    }*/

    /* not used
     * @param array $d
     * @return PDOStatement
     * @throws Exception
    public static function deleteUserCastleUpgrade(array $d)
    {
        $q = "
            DELETE FROM user_castle_upgrade
            WHERE user_id = :user_id
              AND finish_time <= :finish_time;
        ";
        $p = [
            ':user_id' => $d['user_id'],
            ':finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }*/
}
