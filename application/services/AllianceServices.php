<?php

namespace lsb\App\services;

use lsb\Libs\DB;

class AllianceServices
{
    public static function selectUserAlly(array $data)
    {
        $qry = "
            SELECT *
            FROM alliance
            WHERE user_id = :user_id;
        ";
        $param = [':user_id' => $data['user_id']];
        return DB::getSelectResult($qry, $param, true);
    }

    public static function selectUserAllyReq(array $data)
    {
        $qry = "
            SELECT *
            FROM alliance_wait
            WHERE user_id = :user_id;
        ";
        $param = [':user_id' => $data['user_id']];
        return DB::getSelectResult($qry, $param, true);
    }

    public static function selectUserAllyRes(array $data)
    {
        $qry = "
            SELECT *
            FROM alliance_wait
            WHERE friend_id = :user_id;
        ";
        $param = [':user_id' => $data['user_id']];
        return DB::getSelectResult($qry, $param, true);
    }
}
