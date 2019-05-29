<?php

namespace lsb\App\models;

use lsb\Libs\DBConnection;

class UserModel
{
    private static function getDB()
    {
        return DBConnection::getInstance()->getDBConnection();
    }

    public static function getUserWithHive(string $hiveId, int $hiveUid)
    {
        $db = self::getDB();
        $qry = "
            SELECT * FROM `user_platform`
            WHERE `hive_id` = :hive_id
              AND `hive_uid` = :hive_Uid;
        ";
        $stmt = $db->prepare($qry);
        $stmt->bindParam(':hive_id', $hiveId);
        $stmt->bindParam(':hive_uid', $hiveUid);
        return $stmt->execute();
    }
}
