<?php

namespace lsb\App\services;

use lsb\Libs\DB;

class WeaponServices
{
    public static function selectUserWeapon(array $data)
    {
        $qry = "
            SELECT *
            FROM weapon w, weapon_upgrade wu, weapon_crate wc
            WHERE w.user_id = :user_id
              AND w.weapon_id = wu.weapon_id
              AND w.weapon_id = wc.weapon_id;
        ";
        $param = [':user_id' => $data['user_id']];
        return DB::getSelectResult($qry, $param, true);
    }
}
