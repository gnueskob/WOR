<?php

namespace lsb\App\query;

use lsb\App\models\WarDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use PDOStatement;
use Exception;

class WarQuery
{
    public static function selectWarByUser(WarDAO $war)
    {
        $q = "
            SELECT *
            FROM war
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $war->userId];
        return DB::runQuery($q, $p);
    }

    /**
     * @param WarDAO $war
     * @return PDOStatement
     * @throws Exception
     */
    public static function deleteWarByUser(WarDAO $war)
    {
        $q = "
            DELETE FROM war
            WHERE user_id = :user_id
              AND finish_time < :finish_time;
        ";
        $p = [
            ':user_id' => $war->userId,
            ':finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param WarDAO $war
     * @return PDOStatement
     * @throws Exception
     */
    public static function deleteWarByTerritory(WarDAO $war)
    {
        $q = "
            DELETE FROM war
            WHERE territoryId = :territoryId
              AND finish_time < :finish_time;
        ";
        $p = [
            ':territoryId' => $war->territoryId,
            ':finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }
}
