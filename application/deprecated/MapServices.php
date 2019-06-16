<?php

namespace lsb\App\services;

use lsb\Libs\DB;

class MapServices
{
    public static function selectUserTile(array $data)
    {
        $qry = "
            SELECT *
            FROM explore_tile
            WHERE user_id = :user_id;
        ";
        $param = [':user_id' => $data['user_id']];
        return DB::getSelectResult($qry, $param, true);
    }

    public static function selectUserExploration(array $data)
    {
        $qry = "
            SELECT *
            FROM explore_territory
            WHERE user_id = :user_id;
        ";
        $param = [':user_id' => $data['user_id']];
        return DB::getSelectResult($qry, $param, true);
    }
}
