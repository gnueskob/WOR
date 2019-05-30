<?php

namespace lsb\App\services;

use lsb\Libs\Timezone;
use lsb\Libs\DBConnection;
use Exception;

class UserServices
{
    private static function getDB()
    {
        return DBConnection::getInstance()->getDBConnection();
    }

    public static function findHiveUser(array $param)
    {
        $db = self::getDB();
        $qry = "
            SELECT * FROM `user_platform`
            WHERE `hive_id` = :hive_id
              AND `hive_uid` = :hive_Uid;
        ";
        $stmt = $db->prepare($qry);
        $stmt->execute([
            ':hive_id' => $param['hive_id'],
            ':hive_uid', $param['hive_uid']
        ]);
        return $stmt->fetch();
    }

    public static function isTerritoryInUse($data): bool
    {
        $db = self::getDB();
        $qry = "
            SELECT * FROM `user_info`
            WHERE `territory_id` = :territory_id;
        ";
        $stmt = $db->prepare($qry);
        $stmt->bindParam(':territory_id', $data['territory_id']);
        $stmt->execute();
        return !!$stmt->fetch();
    }

    /**
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public static function registerNewAccount(array $data): bool
    {
        $db = self::getDB();
        try {
            $db->beginTransaction();

            $qry = "
                INSERT INTO `user_platform`
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
            $stmt = $db->prepare($qry);
            $stmt->execute([
                ':user_id' => null,
                ':hive_id' => $data['hive_id'],
                ':hive_uid' => $data['hive_uid'],
                ':register_date' => $data['register_date'],
                ':country' => $data['country'],
                ':lang' => $data['lang'],
                ':os_version' => $data['os_version'],
                ':device_name' => $data['device_name'],
                ':app_version' => $data['app_version']
            ]);
            $userId = $db->lastInsertId();

            $qry = "
                INSERT INTO `user_info`
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
            $stmt = $db->prepare($qry);
            $stmt->execute([
                ':user_id' => $userId,
                ':last_update' => Timezone::getNowUTC(),
                ':territory_id' => $data['territory_id'],
                ':name' => null,
                ':castle_level' => 1,
                ':upgrade_finish_time' => null,
                ':auto_generate_manpower' => true,
                ':manpower' => 10,
                ':appended_manpower' => 0,
                ':tactical_resource' => 0,
                ':food_resource' => 0,
                ':luxury_resource' => 0
            ]);
            // TODO: 초기값 기획 데이터 변환

            $qry = "
                INSERT INTO `user_statistic`
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
            $stmt = $db->prepare($qry);
            $stmt->execute([
                ':user_id' => $userId,
                ':war_request' => 0,
                ':war_victory' => 0,
                ':war_defeated' => 0,
                ':despoil_defense_success' => 0,
                ':despoil_defense_fail' => 0,
                ':boss1_kill_count' => 0,
                ':boss2_kill_count' => 0,
                ':boss3_kill_count' => 0
            ]);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
