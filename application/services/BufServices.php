<?php

namespace lsb\App\services;

use lsb\Libs\Timezone;
use lsb\Libs\DB;
use Exception;

class BufServices
{
    /**
     * @param array $data
     * @return array|bool|mixed
     * @throws Exception
     */
    public static function insertUserBuf(array $data)
    {
        $qry = "
            INSERT INTO buf
            VALUE (
                   :buf_id,
                   :user_id,
                   :buf_type,
                   :finish_time,
                   :last_update
            );
        ";
        $param = [
            ':buf_id' => null,
            ':user_id' => $data['user_id'],
            ':buf_type' => $data['buf_type'],
            ':finish_time' => Timezone::getNowUTC(),
            ':last_update' => Timezone::getNowUTC()
        ];
        return DB::getInsertResult($qry, $param);
    }

    /**
     * @param array $data
     * @return array|bool|mixed
     */
    public static function selectUserBuf(array $data)
    {
        $qry = "
            SELECT *
            FROM buf
            WHERE user_id = :user_id;
        ";
        $param = [':user_id' => $data['user_id']];
        return DB::getSelectResult($qry, $param, true);
    }

    /**
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public static function deleteUserBuf(array $data)
    {
        $qry = "
            DELETE FROM buf
            WHERE user_id = :user_id
              AND finish_time <= :finish_time;
        ";
        $param = [
            ':user_id' => $data['user_id'],
            ':finish_time' => Timezone::getNowUTC()
        ];
        return DB::getResultRowCount($qry, $param);
    }
}
