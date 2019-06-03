<?php

namespace lsb\App\services;

use lsb\Libs\Timezone;
use lsb\Libs\DB;
use Exception;
use PDOException;

class UserServices
{
    public static function updateUserLastVisit(array $data)
    {
        $qry = "
            UPDATE user_info
            SET last_visit = :last_visit
            WHERE user_id = :user_id;
        ";
        $param = [
            ':last_visit' => $data['last_visit'],
            ':hive_id' => $data['hive_id'],
        ];
        return DB::getResultRowCount($qry, $param);
    }

    /**
     * @param array $data
     * @return mixed    false: there are no record, array: any selected record
     */
    public static function selectHiveUser(array $data)
    {
        $qry = "
            SELECT * FROM user_platform
            WHERE hive_id = :hive_id
              AND hive_uid = :hive_uid;
        ";
        $param = [
            ':hive_id' => $data['hive_id'],
            ':hive_uid', $data['hive_uid']
        ];
        return DB::getSelectResult($qry, $param);
    }

    /**
     * @param array $data
     * @return int      -1 : register failed, {number} : registered user_id
     * @throws Exception
     */
    public static function registerNewAccount(array $data): int
    {
        $dbMngr = DB::getInstance();
        $db = $dbMngr->getDBConnection();
        try {
            $db->beginTransaction();
            $qry = "
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
            $param = [
                ':user_id' => null,
                ':hive_id' => $data['hive_id'],
                ':hive_uid' => $data['hive_uid'],
                ':register_date' => $data['register_date'],
                ':country' => $data['country'],
                ':lang' => $data['lang'],
                ':os_version' => $data['os_version'],
                ':device_name' => $data['device_name'],
                ':app_version' => $data['app_version']
            ];
            $dbMngr->query($qry, $param);
            $userId = $db->lastInsertId();

            $qry = "
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
            $param = [
                ':user_id' => $userId,
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
            // TODO: 초기값 기획 데이터 변환
            $dbMngr->query($qry, $param);

            $qry = "
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
            $param = [
                ':user_id' => $userId,
                ':war_request' => 0,
                ':war_victory' => 0,
                ':war_defeated' => 0,
                ':despoil_defense_success' => 0,
                ':despoil_defense_fail' => 0,
                ':boss1_kill_count' => 0,
                ':boss2_kill_count' => 0,
                ':boss3_kill_count' => 0
            ];
            $dbMngr->query($qry, $param);
            if ($db->commit() === false) {
                return -1;
            }
        } catch (PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
        return $userId;
    }

    /**
     * @param array $data
     * @return bool|int     false: duplicated name, {number}: # of updated records
     */
    public static function updateUserName(array $data)
    {
        $qry = "
            UPDATE user_info
            SET name = :name
            WHERE user_id = :user_id;
        ";
        $param = [
            ':name' => $data['name'],
            ':user_id' => $data['id']
        ];
        try {
            return DB::getResultRowCount($qry, $param);
        } catch (PDOException $e) {
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                return false;
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param array $data
     * @return bool|int     false: duplicated name, {number}: # of updated records
     */
    public static function updateUserTerritory(array $data): bool
    {
        $qry = "
            UPDATE user_info
            SET territory_id = :territory_id
            WHERE user_id = :user_id;
        ";
        $param = [
            ':territory_id' => $data['territory_id'],
            ':user_id' => $data['user_id']
        ];
        try {
            return DB::getResultRowCount($qry, $param);
        } catch (PDOException $e) {
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                return false;
            } else {
                throw $e;
            }
        }
    }

    public static function selectUserInfo(array $data)
    {
        $qry = "
            SELECT *
            FROM user_platform up, user_info ui, user_statistics us
            WHERE up.user_id = ui.user_id
              AND up.user_id = us.user_id
              AND up.user_id = :user_id;
        ";
        $param = [':user_id' => $data['user_id']];
        return DB::getSelectResult($qry, $param);
    }
}
