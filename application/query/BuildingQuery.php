<?php

namespace lsb\App\query;

use lsb\App\models\BuildingDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;

class BuildingQuery
{
    public static function selectBuilding(BuildingDAO $building)
    {
        $q = "
            SELECT *
            FROM building
            WHERE building_id = :building_id;
        ";
        $p = [':building_id' => $building->buildingId];
        return DB::runQuery($q, $p);
    }

    public static function selectBuildingsById(array $buildingIds)
    {
        $q = "
            SELECT *
            FROM building
            WHERE building_id IN (:building_ids);
        ";
        $p = [':building_id' => implode(',', $buildingIds)];
        return DB::runQuery($q, $p);
    }

    public static function selectBuildingByUser(BuildingDAO $building)
    {
        $q = "
            SELECT b.*
            FROM building
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $building->userId];
        return DB::runQuery($q, $p);
    }

    /* 각 테이블 별로 쿼리 분할 시 비용 하락으로 더 좋으나... 시간 없음 ㅎ;
    public static function selectBuildingUpgrade(array $d)
    {
        $q = "
            SELECT *
            FROM building_upgrade
            WHERE building_id = :building_id;
        ";
        $p = [':building_id' => $d['building_id']];
        return DB::runQuery($q, $p);
    }*/

    public static function updateBuildingWithLevel(BuildingDAO $building)
    {
        $q = "
            UPDATE building
            SET level = :level,
                to_level = :to_level,
                upgrade_time = :upgrade_time
            WHERE building_id = :building_id;
        ";
        $p = [
            ':level' => $building->level,
            ':to_level' => $building->toLevel,
            ':upgrade_time' => $building->upgradeTime
        ];
        return DB::runQuery($q, $p);
    }

    /*
    public static function updateBuildingWithCreateTime(array $d)
    {
        $q = "
            UPDATE building
            SET create_time = :create_time
            WHERE building_id = :building_id;
        ";
        $p = [
            ':building_id' => $d['building_id'],
            ':create_time' => $d['create_time']
        ];
        return DB::runQuery($q, $p);
    }*/

    public static function updateBuildingWithDeployTimeAndManpower(BuildingDAO $building)
    {
        $q = "
            UPDATE building
            SET deploy_time = :deploy_time,
                manpower = :manpower
            WHERE building_id = :building_id;
        ";
        $p = [
            ':deploy_time' => $building->deployTime,
            ':manpower' => $building->manpower,
            ':building_id' => $building->buildingId
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param BuildingDAO $building
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertBuilding(BuildingDAO $building)
    {
        $q = "
            INSERT INTO building
            VALUE (
                   :building_id,
                   :user_id,
                   :territory_id,
                   :tile_id,
                   :building_type,
                   :create_time,
                   :deploy_time,
                   :upgrade_time,
                   :upgrade,
                   :manpower,
                   :last_update
            );
        ";
        $p = [
            ':building_id' => null,
            ':user_id' => $building->userId,
            ':territory_id' => $building->territoryId,
            ':tile_id' => $building->tileId,
            ':building_type' => $building->buildingType,
            ':create_time' => $building->createTime,
            ':deploy_time' => null,
            ':upgrade_time' => null,
            ':upgrade' => 1,
            ':manpower' => 0,
            ':last_update' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

    /*
    public static function insertBuildingCreate(array $d)
    {
        $q = "
            INSERT INTO building_create
            VALUE (
                   :crate_id,
                   :building_id,
                   :user_id,
                   :create_finish_time
            );
        ";
        $p = [
            ':crate_id' => null,
            ':building_id' => $d['building_id'],
            ':user_id' => $d['user_id'],
            ':create_finish_time' => $d['create_finish_time']
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertBuildingUpgrade(array $d)
    {
        $q = "
            INSERT INTO building_upgrade
            VALUE (
                   :upgrade_id,
                   :building_id,
                   :user_id,
                   :from_level,
                   :to_level,
                   :done,
                   :upgrade_finish_time
            );
        ";
        $p = [
            ':upgrade_id' => null,
            ':building_id' => $d['building_id'],
            ':user_id' => $d['user_id'],
            ':from_level' => $d['from_level'],
            ':to_level' => $d['to_level'],
            ':done' => null,
            ':upgrade_finish_time' => $d['upgrade_finish_time']
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertBuildingDeploy(array $d)
    {
        $q = "
            INSERT INTO building_deploy
            VALUE (
                   :upgrade_id,
                   :building_id,
                   :user_id,
                   :done,
                   :deploy_finish_time
            );
        ";
        $p = [
            ':upgrade_id' => null,
            ':building_id' => $d['building_id'],
            ':user_id' => $d['user_id'],
            ':done' => null,
            ':deploy_finish_time' => $d['deploy_finish_time']
        ];
        return DB::runQuery($q, $p);
    }

    public static function deleteBuildingCreate(array $d)
    {
        $q = "
            DELETE FROM building_create
            WHERE building_id = :building_id
              AND create_finish_time <= :create_finish_time;
        ";
        $p = [
            ':building_id' => $d['building_id'],
            ':create_finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

    public static function deleteBuildingUpgrade(array $d)
    {
        $q = "
            DELETE FROM building_upgrade
            WHERE building_id = :building_id
              AND upgrade_finish_time <= :upgrade_finish_time;
        ";
        $p = [
            ':building_id' => $d['building_id'],
            ':upgrade_finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

    public static function deleteBuildingDeploy(array $d)
    {
        $q = "
            DELETE FROM building_deploy
            WHERE building_id = :building_id
              AND deploy_finish_time <= :deploy_finish_time;
        ";
        $p = [
            ':building_id' => $d['building_id'],
            ':deploy_finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }
    */
}
