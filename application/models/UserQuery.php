<?php

namespace lsb\App\models;

use lsb\Libs\DB;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;

class UserQuery
{
    // TODO: 함수 이름 정리
    public static function selectHiveUser(array $d)
    {
        $q = "
            SELECT user_id
            FROM user_platform
            WHERE hive_id = :hive_id
              AND hive_uid = :hive_uid;
        ";
        $p = [
            ':hive_id' => $d['hive_id'],
            ':hive_uid', $d['hive_uid']
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserLastVisit(array $d)
    {
        $q = "
            UPDATE user_info
            SET last_visit = :last_visit
            WHERE user_id = :user_id;
        ";
        $p = [
            ':last_visit' => $d['last_visit'],
            ':hive_id' => $d['hive_id'],
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertUserPlatform(array $d)
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
            ':hive_id' => $d['hive_id'],
            ':hive_uid' => $d['hive_uid'],
            ':register_date' => $d['register_date'],
            ':country' => $d['country'],
            ':lang' => $d['lang'],
            ':os_version' => $d['os_version'],
            ':device_name' => $d['device_name'],
            ':app_version' => $d['app_version']
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertUserInfo(array $d)
    {
        $q = "
            INSERT INTO user_info
            VALUE (
                    :user_id,
                    :last_update,
                    :territory_id,
                    :name,
                    :castle_level,
                    :upgrade_finish_time,
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
            ':user_id' => $d['user_id'],
            ':last_update' => Timezone::getNowUTC(),
            ':territory_id' => 0,
            ':name' => null,
            ':castle_level' => 1,
            ':upgrade_finish_time' => null,
            ':auto_generate_manpower' => true,
            ':manpower' => 10,
            ':appended_manpower' => 0,
            ':tactical_resource' => 0,
            ':food_resource' => 0,
            ':luxury_resource' => 0
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertUserStatistics(array $d)
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
            ':user_id' => $d['user_id'],
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

    public static function updateUserName(array $d)
    {
        $q = "
            UPDATE user_info
            SET name = :name
            WHERE user_id = :user_id;
        ";
        $p = [
            ':name' => $d['name'],
            ':user_id' => $d['id']
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserTerritory(array $d)
    {
        $q = "
            UPDATE user_info
            SET territory_id = :territory_id
            WHERE user_id = :user_id;
        ";
        $p = [
            ':territory_id' => $d['territory_id'],
            ':user_id' => $d['user_id']
        ];
        return DB::runQuery($q, $p);
    }

    public static function selectUser(array $d)
    {
        $q = "
            SELECT *
            FROM user_platform up, user_info ui, user_statistics us
            WHERE up.user_id = ui.user_id
              AND up.user_id = us.user_id
              AND up.user_id = :user_id;
        ";
        $p = [':user_id' => $d['user_id']];
        return DB::runQuery($q, $p);
    }

    public static function updateUserResource(array $d)
    {
        $q = "
            UPDATE user_info
            SET tactical_resource = tactical_resource - :need_tactical_resource,
                food_resource = food_resource - :need_food_resource,
                luxury_resource = luxury_resource - :need_luxury_resource
            WHERE user_id = :user_id;
        ";
        $p = [
            ':need_tactical_resource' => $d['need_tactical_resource'],
            ':need_food_resource' => $d['need_food_resource'],
            ':need_luxury_resource' => $d['need_luxury_resource'],
            ':user_id' => $d['user_id']
        ];
        return DB::runQuery($q, $p);
    }

    public static function selectUserInfo(array $d)
    {
        $q = "
            SELECT *
            FROM user_info
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $d['user_id']];
        return DB::runQuery($q, $p);
    }

    public static function insertUserCastleUpgrade(array $d)
    {
        $q = "
            INSERT INTO user_castle_upgrade
            VALUE (
                   :id,
                   :user_id,
                   :from_level,
                   :to_level,
                   :finish_time
            );
        ";
        $p = [
            ':id' => null,
            ':user_id' => $d['user_id'],
            ':from_level' => $d['from_level'],
            ':to_level' => $d['to_level'],
            ':finish_time' => $d['finish_time']
        ];
        return DB::runQuery($q, $p);
    }

    public static function selectUserCastleUpgrade(array $d)
    {
        $q = "
            SELECT *
            FROM user_castle_upgrade
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $d['user_id']];
        return DB::runQuery($q, $p);
    }

    public static function deleteUserCastleUpgrade(array $d)
    {
        $q = "
            DELETE FROM user_castle_upgrade
            WHERE user_id = :user_id
              AND finish_time <= :finish_time;
        ";
        $p = [
            ':user_id' => $d['user_id'],
            ':finish_time' => $d['finish_time']
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserCastleUpgrade(array $d)
    {
        $q = "
            UPDATE user_castle_upgrade
            SET upgrade = :upgrade
            WHERE user_id = :user_id;
        ";
        $p = [
            ':upgrdae' => $d['upgrade'],
            ':user_id' => $d['user_id']
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserUsedManpower(array $d)
    {
        $q = "
            UPDATE user_info
            SET manpower_used = manpower_used + :manpower_used
            WHERE user_id = :user_id;
        ";
        $p = [
            ':manpower_used' => $d['manpower_used'],
            ':user_id' => $d['user_id']
        ];
        return DB::runQuery($q, $p);
    }
}
