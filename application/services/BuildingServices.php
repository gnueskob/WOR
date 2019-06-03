<?php

namespace lsb\App\services;

use lsb\Libs\CtxException;
use lsb\Libs\Timezone;
use lsb\Libs\DB;
use Exception;
use PDOException;

class BuildingServices
{
    /**
     * @param array $data
     * @return array|bool|mixed
     * @throws Exception
     */
    public static function insertUserBuilding(array $data)
    {
        $qry = "
            INSERT INTO building
            VALUE (
                   :building_id,
                   :user_id,
                   :territory_id,
                   :tile_id,
                   :building_type,
                   :create_finish_time,
                   :deploy_finish_time,
                   :upgrade_finish_time,
                   :upgrade,
                   :manpower,
                   :last_update
            );
        ";
        $param = [
            ':building_id' => null,
            ':user_id' => $data['user_id'],
            ':territory_id' => $data['territory_id'],
            ':tile_id' => $data['tile_id'],
            ':building_type' => $data['building_type'],
            ':create_finish_time' => null,
            ':deploy_finish_time' => null,
            ':upgrade_finish_time' => null,
            ':upgrade' => 1,
            ':manpower' => 0,
            ':last_update' => Timezone::getNowUTC()
        ];
        return DB::getInsertResult($qry, $param);
    }

    public static function selectUserBuilding(array $data)
    {
        $qry = "
            SELECT *
            FROM building b, building_upgrade bu, building_deploy bd, building_crate bc
            WHERE b.user_id = :user_id
              AND b.building_id = bu.building_id
              AND b.building_id = bd.building_id
              AND b.building_id = bc.building_id;
        ";
        $param = [':user_id' => $data['user_id']];
        return DB::getSelectResult($qry, $param, true);
    }

    public static function selectBuilding(array $data)
    {
        $qry = "
            SELECT *
            FROM building
            WHERE building_id = :building_id;
        ";
        $param = [':building_id' => $data['building_id']];
        return DB::getSelectResult($qry, $param);
    }

    public static function insertBuildingUpgrade(array $data)
    {
        $qry = "
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
        $param = [
            ':upgrade_id' => null,
            ':building_id' => $data['building_id'],
            ':user_id' => $data['user_id'],
            ':from_level' => $data['from_level'],
            ':to_level' => $data['to_level'],
            ':done' => null,
            ':upgrade_finish_time' => $data['upgrade_finish_time']
        ];
        try {
            return DB::getInsertResult($qry, $param);
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
     * @return int
     * @throws Exception
     */
    public static function updateBuildingUpgrade(array $data)
    {
        $q = "
            UPDATE building b, building_upgrade bu
            SET b.upgrade = bu.to_level,
                bu.done = 1
            WHERE b.building_id = :building_id
              AND b.building_id = bu.building_id
              AND bu.finish_time <= :finish_time;
        ";
        $p = [
            ':building_id' => $data['building_id'],
            ':finish_time' => Timezone::getNowUTC()
        ];
        return DB::getResultRowCount($q, $p);
    }

    public static function deleteBuildingUpgrade(array $data)
    {
        $q = "
            DELETE FROM building_upgrade
            WHERE building_id = :building_id
              AND done = 1;
        ";
        $p = [':building_id' => $data['building_id']];
        return DB::getResultRowCount($q, $p);
    }
}
