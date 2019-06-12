<?php

namespace lsb\App\query;

use lsb\App\models\WarDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use PDOStatement;
use Exception;

class WarQuery
{
    public static function selectWar(WarDAO $war)
    {
        $q = "
            SELECT *
            FROM war
            WHERE war_id = :war_id;
        ";
        $p = [':war_id' => $war->warId];
        return DB::runQuery($q, $p);
    }

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

    public static function insertWar(WarDAO $war)
    {
        $q = "
            INSERT INTO war
            VALUE (
                :war_id,
                :user_id,
                :territory_id,
                :attack,
                :manpower,
                :building_list,
                :food_resource,
                :target_defense,
                :prepare_time,
                :finish_time
            );
        ";
        $p = [
            ':war_id' => null,
            ':user_id' => $war->userId,
            ':territory_id' => $war->territoryId,
            ':attack' => $war->attack,
            ':manpower' => $war->manpower,
            ':building_list' => $war->buildingList,
            ':food_resource' => $war->foodResource,
            ':target_defense' => $war->targetDefense,
            ':prepare_time' => $war->prepareTime,
            ':finish_time' => $war->finishTime
        ];
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
