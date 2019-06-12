<?php

namespace lsb\App\query;

use lsb\App\models\BuffDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use PDOStatement;
use Exception;

class BuffQuery
{
    public static function selectBuffByUser(BuffDAO $buff)
    {
        $q = "
            SELECT *
            FROM buff
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $buff->userId];
        return DB::runQuery($q, $p);
    }

    public static function selectBuffByUserAndType(BuffDAO $buff)
    {
        $q = "
            SELECT *
            FROM buff
            WHERE user_id = :user_id
              AND buff_type = :buff_type;
        ";
        $p = [
            ':user_id' => $buff->userId,
            ':buff_type' => $buff->buffType
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertBuff(BuffDAO $buff)
    {
        $q = "
            INSERT INTO buff
            VALUE (
                    :buff_id,
                    :user_id,
                    :buff_type,
                    :finish_time
            );
        ";
        $p = [
            ':buff_id' => null,
            ':user_id' => $buff->userId,
            ':buff_type' => $buff->buffType,
            ':finish_time' => $buff->finishTime
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param BuffDAO $buff
     * @return PDOStatement
     * @throws Exception
     */
    public static function deleteBuffByUser(BuffDAO $buff)
    {
        $q = "
            DELETE FROM buff
            WHERE user_id = :user_id
              AND finish_time < :finish_time;
        ";
        $p = [
            ':user_id' => $buff->userId,
            ':finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }
}
