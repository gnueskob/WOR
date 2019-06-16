<?php

namespace lsb\App\query;

use lsb\App\models\BuffDAO;
use lsb\App\models\Query;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use PDOStatement;
use Exception;

class BuffQuery extends Query
{
    public function __construct()
    {
        parent::__construct(BuffDAO::getColumnMap());
    }

    public static function buff()
    {
        return static::make()->setTable('buff');
    }

    /************************************************************/

    public function whereUserId(int $userId)
    {
        return $this->whereEqual(['userId' => $userId]);
    }

    public function whereBuffId(int $buffId)
    {
        return $this->whereEqual(['buffId' => $buffId]);
    }

    protected function whereExpired(string $time)
    {
        return $this->whereLT(['finishTime' => $time]);
    }

    /************************************************************/

    // SELECT QUERY

    public static function qSelectBuff(BuffDAO $dao)
    {
        return static::buff()
            ->selectQurey()
            ->selectAll()
            ->whereBuffId($dao->buffId);
    }

    public static function qSelectBuffByUser(BuffDAO $dao)
    {
        return static::buff()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId);
    }

    /************************************************************/

    // INSERT QUERY

    public static function qInsertBuff(BuffDAO $dao)
    {
        return static::buff()
            ->insertQurey()
            ->value([
                'buffId' => $dao->buffId,
                'userId' => $dao->userId,
                'buffType' => $dao->buffType,
                'finishTime' => $dao->finishTime,
            ]);
    }

    /************************************************************/

    // DELETE QUERY

    public static function qDeleteExpiredBuff(BuffDAO $dao)
    {
        return static::buff()
            ->deleteQurey()
            ->whereUserId($dao->userId)
            ->whereExpired($dao->finishTime);
    }

    /************************************************************/

    /************************************************************/

    /*
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
    */
}
