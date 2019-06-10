<?php

namespace lsb\App\query;

use lsb\App\models\BufDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use PDOStatement;
use Exception;

class BufQuery
{
    public static function selectBufByUser(BufDAO $buf)
    {
        $q = "
            SELECT *
            FROM buf
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $buf->userId];
        return DB::runQuery($q, $p);
    }

    public static function selectBufByUserAndType(BufDAO $buf)
    {
        $q = "
            SELECT *
            FROM buf
            WHERE user_id = :user_id
              AND buf_type = :buf_type;
        ";
        $p = [
            ':user_id' => $buf->userId,
            ':buf_type' => $buf->bufType
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertBuf(BufDAO $buf)
    {
        $q = "
            INSERT INTO buf
            VALUE (
                    :buf_id,
                    :user_id,
                    :buf_type,
                    :finish_time
            );
        ";
        $p = [
            ':buf_id' => null,
            ':user_id' => $buf->userId,
            ':buf_type' => $buf->bufType,
            ':finish_time' => $buf->finishTime
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param BufDAO $buf
     * @return PDOStatement
     * @throws Exception
     */
    public static function deleteBufByUser(BufDAO $buf)
    {
        $q = "
            DELETE FROM buf
            WHERE user_id = :user_id
              AND finish_time < :finish_time;
        ";
        $p = [
            ':user_id' => $buf->userId,
            ':finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }
}
