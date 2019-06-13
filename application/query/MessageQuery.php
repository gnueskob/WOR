<?php

namespace lsb\App\query;

use lsb\App\models\MessageDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use PDOStatement;
use Exception;

define('MSG_BD_CRE', 'building_create');
define('MSG_BD_UPG', 'building_upgrade');
define('MSG_BD_DEP', 'building_deploy');
define('MSG_WP_CRE', 'weapon_create');
define('MSG_WP_UPG', 'weapon_upgrade');
define('MSG_WAR_FNS', 'war_finish');


class MessageQuery
{
    public static $targetList = [
        'building_create',
        'building_upgrade',
        'building_deploy',
        'weapon_create',
        'weapon_upgrade',
        'war_finish'
    ];

    public static function selectMessage(MessageDAO $msg)
    {
        $q = "
            SELECT *
            FROM msg_{$msg->target}
            WHERE user_id = :user_id,
              AND active_time <= :active_time;
        ";
        $p = [
            ':user_id' => $msg->userId,
            ':active_time' => $msg->activeTime
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param MessageDAO $msg
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertMessage(MessageDAO $msg)
    {
        $q = "
            INSERT INTO msg_{$msg->target}
            VALUE (
                    :msg_id,
                    :user_id,
                    :target_id,
                    :create_time,
                    :active_time
            );
        ";
        $p = [
            ':msg_id' => null,
            ':user_id' => $msg->userId,
            ':target_id' => $msg->targetId,
            ':create_time' => Timezone::getNowUTC(),
            ':active_time' => $msg->activeTime
        ];
        return DB::runQuery($q, $p);
    }

    public static function deleteMessage(MessageDAO $msg)
    {
        $q = "
            DELETE FROM msg_{$msg->target}
            WHERE user_id = :user_id
              AND target_id = :target_id;
        ";
        $p = [
            ':user_id' => $msg->userId,
            ':target_id' => $msg->targetId
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param MessageDAO $msg
     * @return PDOStatement
     * @throws Exception
     */
    public static function deleteMessageByUser(MessageDAO $msg)
    {
        $q = "
            DELETE FROM msg_{$msg->target}
            WHERE user_id = :user_id
              AND active_time < :active_time;
        ";
        $p = [
            ':user_id' => $msg->userId,
            ':active_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }
}
