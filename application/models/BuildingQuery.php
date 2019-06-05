<?php

namespace lsb\App\models;

use lsb\Libs\DB;
use lsb\Libs\Timezone;
use Exception;
use PDOStatement;

class BuildingQuery
{
    public static function selectBuilding(array $d)
    {
        $q = "
            SELECT b.*,
                    bu.to_level, bu.from_level, bu.done, bu.upgrade_finish_time,
                    bc.create_finish_time,
                    bd.deploy_finish_time
            FROM building b LEFT JOIN building_upgrade bu ON b.building_id = bu.building_id
               LEFT JOIN building_deploy bd ON b.building_id = bd.building_id
               LEFT JOIN building_create bc ON b.building_id = bc.building_id
            WHERE b.building_id = :building_id;
        ";
        $p = [':building_id' => $d['building_id']];
        return DB::runQuery($q, $p);
    }

    public static function selectBuildingByUser(array $d)
    {
        $q = "
            SELECT b.*,
                    bu.to_level, bu.from_level, bu.done, bu.upgrade_finish_time,
                    bc.create_finish_time,
                    bd.deploy_finish_time
            FROM building b LEFT JOIN building_upgrade bu ON b.building_id = bu.building_id
               LEFT JOIN building_deploy bd ON b.building_id = bd.building_id
               LEFT JOIN building_create bc ON b.building_id = bc.building_id
            WHERE b.user_id = :user_id;
        ";
        $p = [':user_id' => $d['user_id']];
        return DB::runQuery($q, $p);
    }

    // 각 테이블 별로 쿼리 분할 시 비용 하락으로 더 좋으나... 시간 없음 ㅎ;
    public static function selectBuildingUpgrade(array $d)
    {
        $q = "
            SELECT *
            FROM building_upgrade
            WHERE building_id = :building_id;
        ";
        $p = [':building_id' => $d['building_id']];
        return DB::runQuery($q, $p);
    }

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
    public static function updateBuildingWithUpgrade(array $d)
    {
        $q = "
            UPDATE building
            SET upgrade = :upgrade,
                upgrade_time = :upgrade_time
            WHERE building_id = :building_id;
        ";
        $p = [
            ':building_id' => $d['building_id'],
            ':upgrade' => $d['upgrade'],
            ':upgrade_time' => $d['upgrade_time']
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
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
    }

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
    public static function updateBuildingWithDeployTimeManpower(array $d)
    {
        $q = "
            UPDATE building
            SET deploy_time = :deploy_time,
                manpower = :manpower
            WHERE building_id = :building_id;
        ";
        $p = [
            ':building_id' => $d['building_id'],
            ':deploy_time' => $d['deploy_time']
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertBuilding(array $d)
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
            ':user_id' => $d['user_id'],
            ':territory_id' => $d['territory_id'],
            ':tile_id' => $d['tile_id'],
            ':building_type' => $d['building_type'],
            ':create_time' => null,
            ':deploy_time' => null,
            ':upgrade_time' => null,
            ':upgrade' => 1,
            ':manpower' => 0,
            ':last_update' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

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

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
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

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
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

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
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
}
