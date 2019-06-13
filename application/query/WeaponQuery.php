<?php

namespace lsb\App\query;

use lsb\App\models\WeaponDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use Exception;
use lsb\Utils\Utils;
use PDOStatement;

class WeaponQuery
{
    public static function selectWeapon(WeaponDAO $weapon)
    {
        $q = "
            SELECT *
            FROM weapon
            WHERE weapon_id = :weapon_id;
        ";
        $p = [':weapon_id' => $weapon->weaponId];
        return DB::runQuery($q, $p);
    }

    public static function selectWeaponsByUser(WeaponDAO $weapon)
    {
        $q = "
            SELECT *
            FROM weapon
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $weapon->userId];
        return DB::runQuery($q, $p);
    }

    /**
     * @param WeaponDAO $weapon
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertWeapon(WeaponDAO $weapon)
    {
        $q = "
            INSERT INTO weapon
            VALUE (
                   :weapon_id,
                   :user_id,
                   :weapon_type,
                   :upgrade,
                   :upgrade_time,
                   :create_time,
                   :last_update
            );
        ";
        $p = [
            ':weapon_id' => null,
            ':user_id' => $weapon->userId,
            ':weapon_type' => $weapon->weaponType,
            ':upgrade' => 1,
            ':upgrade_time' => null,
            ':create_time' => null,
            ':last_update' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertWeaponCreate(array $d)
    {
        $q = "
            INSERT INTO weapon_create
            VALUE (
                   :crate_id,
                   :weapon_id,
                   :user_id,
                   :create_finish_time
            );
        ";
        $p = [
            ':crate_id' => null,
            ':weapon_id' => $d['weapon_id'],
            ':user_id' => $d['user_id'],
            ':create_finish_time' => $d['create_finish_time']
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertWeaponUpgrade(array $d)
    {
        $q = "
            INSERT INTO weapon_upgrade
            VALUE (
                   :upgrade_id,
                   :weapon_id,
                   :user_id,
                   :from_level,
                   :to_level,
                   :upgrade_finish_time
            );
        ";
        $p = [
            ':upgrade_id' => null,
            ':weapon_id' => $d['weapon_id'],
            ':user_id' => $d['user_id'],
            ':from_level' => $d['from_level'],
            ':to_level' => $d['to_level'],
            ':upgrade_finish_time' => $d['upgrade_finish_time']
        ];
        return DB::runQuery($q, $p);
    }

    /*
    public static function updateWeaponWithCreateTime(array $d)
    {
        $q = "
            UPDATE weapon
            SET create_time = :create_time
            WHERE weapon_id = :weapon_id;
        ";
        $p = [
            ':weapon_id' => $d['weapon_id'],
            ':create_time' => $d['create_time']
        ];
        return DB::runQuery($q, $p);
    }
    */

    public static function updateBuildingAll(WeaponDAO $container, bool $assign = false)
    {
        $set = Utils::makeSetClause($container, $assign);
        $q = "
            UPDATE weapon
            SET {$set}
            WHERE weapon_id = :weapon_id;
        ";
        $p = Utils::makeBindParameters($container);
        $p[':weapon_id'] = $container->weaponId;
        return DB::runQuery($q, $p);
    }

    public static function updateWeaponWithLevel(WeaponDAO $weapon)
    {
        $q = "
            UPDATE weapon
            SET upgrade_time = :upgrade_time,
                level = :level,
                to_level = :to_level
            WHERE weapon_id = :weapon_id;
        ";
        $p = [
            ':upgrade_time' => $weapon->upgradeTime,
            ':level' => $weapon->level,
            ':to_level' => $weapon->toLevel,
            ':weapon_id' => $weapon->weaponId
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
    public static function deleteWeaponCreate(array $d)
    {
        $q = "
            DELETE FROM weapon_create
            WHERE weapon_id = :weapon_id
              AND create_finish_time <= :create_finish_time;
        ";
        $p = [
            ':weapon_id' => $d['weapon_id'],
            ':create_finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param array $d
     * @return PDOStatement
     * @throws Exception
     */
    public static function deleteWeaponUpgrade(array $d)
    {
        $q = "
            DELETE FROM weapon_upgrade
            WHERE weapon_id = :weapon_id
              AND upgrade_finish_time <= :upgrade_finish_time;
        ";
        $p = [
            ':weapon_id' => $d['weapon_id'],
            ':upgrade_finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }
}
