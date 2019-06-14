<?php

namespace lsb\App\query;

use lsb\App\models\Query;
use lsb\App\models\UserDAO;
use lsb\Libs\DB;
use lsb\Libs\Timezone;
use Exception;
use lsb\Utils\Utils;
use PDOStatement;

class UserQuery extends Query
{
    public function __construct()
    {
        parent::__construct(UserDAO::getColumnMap());
    }

    public static function userInfo()
    {
        return static::make()->setTable('user_info');
    }

    public static function userPlatform()
    {
        return static::make()->setTable('user_platform');
    }

    public static function userStat()
    {
        return static::make()->setTable('user_statistics');
    }

    /**************************************************************/

    public function duplicateCheck()
    {
        // TODO:
    }

    /**************************************************************/

    public function whereHiveUser($hiveId, $hiveUid)
    {
        return $this->whereEqual([
            'hiveId' => $hiveId,
            'hiveUid' => $hiveUid
        ]);
    }

    public function whereUserId($userId)
    {
        return $this->whereEqual(['userId' => $userId]);
    }

    /**************************************************************/

    public static function qSelectHiveUser(UserDAO $dao)
    {
        return static::userPlatform()
            ->selectQurey()
            ->select(['userId'])
            ->whereHiveUser($dao->hiveId, $dao->hiveUid);
    }

    /**
     * @param UserDAO $dao
     * @return UserQuery
     * @throws Exception
     */
    public static function qUpdateUserLastVisit(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->set(['lastVisit' => Timezone::getNowUTC()])
            ->whereUserId($dao->userId);
    }

    public static function qSelectUserInfo(UserDAO $dao)
    {
        return static::userInfo()
            ->selectAll()
            ->whereUserId($dao->userId);
    }


    public static function qSelectUserInfoByTerritory(UserDAO $dao)
    {
        return static::userInfo()
            ->selectAll()
            ->whereUserId($dao->userId);
    }

    public static function qUpdateUserInfoSetName(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->set(['name' => $dao->name])
            ->whereEqual($dao->userId);
    }

    /**************************************************************/

    public static function selectUser(UserDAO $dao)
    {
        $q = "
            SELECT up.*, ui.*, us.*
            FROM user_platform up 
                JOIN user_info ui ON up.user_id = ui.user_id
                JOIN user_statistics us ON up.user_id = us.user_id
            WHERE up.user_id = :user_id;
        ";
        $p = [':user_id' => $dao->userId];
        return static::make()->setQuery($q, $p);
    }

    public static function selectUserPlatformByHive(UserDAO $user)
    {
        $q = "
            SELECT user_id
            FROM user_platform
            WHERE hive_id = :hive_id
              AND hive_uid = :hive_uid;
        ";
        $p = [
            ':hive_id' => $user->hiveId,
            ':hive_uid' => $user->hiveUid
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoAll(UserDAO $user, bool $assign = false)
    {
        $set = Utils::makeSetClause($user, $assign);
        $q = "
            UPDATE user_info
            SET {$set}
            WHERE user_id = :user_id;
        ";
        $p = Utils::makeBindParameters($user);
        $p[':user_id'] = $user->userId;
        return DB::runQuery($q, $p);
    }

    /*
    public static function updateUserInfoWithLastVisit(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET last_visit = :last_visit
            WHERE user_id = :user_id;
        ";
        $p = [
            ':user_id' => $user->userId,
            ':last_visit' => $user->lastVisit
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithName(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET name = :name
            WHERE user_id = :user_id;
        ";
        $p = [
            ':name' => $user->name,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithTerritory(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET territory_id = :territory_id
            WHERE user_id = :user_id;
        ";
        $p = [
            ':territory_id' => $user->territoryId,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithResource(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET tactical_resource = tactical_resource + :tactical_resource,
                food_resource = food_resource + :food_resource,
                luxury_resource = luxury_resource + :luxury_resource
            WHERE user_id = :user_id;
        ";
        $p = [
            ':tactical_resource' => $user->tacticalResource,
            ':food_resource' => $user->foodResource,
            ':luxury_resource' => $user->luxuryResource,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithPenaltyTime(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET penalty_finish_time = :penalty_finish_time
            WHERE user_id = :user_id;
        ";
        $p = [
            ':penalty_finish_time' => $user->penaltyFinishTime,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithUpgradeAndResource(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET tactical_resource = :tactical_resource,
                food_resource = :food_resource,
                luxury_resource = :luxury_resource,
                castle_level = :castle_level,
                castle_to_level = :castle_to_level,
                to_level = :to_level
            WHERE user_id = :user_id;
        ";
        $p = [
            ':tactical_resource' => $user->tacticalResource,
            ':food_resource' => $user->foodResource,
            ':luxury_resource' => $user->luxuryResource,
            ':castle_level' => $user->castleLevel,
            ':castle_to_level' => $user->castleToLevel,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }

    public static function updateUserInfoWithUsedManpower(UserDAO $user)
    {
        $q = "
            UPDATE user_info
            SET manpower_used = manpower_used + :manpower_used
            WHERE user_id = :user_id;
        ";
        $p = [
            ':manpower_used' => $user->manpowerUsed,
            ':user_id' => $user->userId
        ];
        return DB::runQuery($q, $p);
    }*/

    /**
     * @param UserDAO $user
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertUserPlatform(UserDAO $user)
    {
        return static::userPlatform()
            ->insertQurey()
            ->value([
                'userId' => $user->userId,
                'hive'
            ]);
        $q = "
            INSERT INTO user_platform
            VALUE (
                    :user_id,
                    :hive_id,
                    :hive_uid,
                    :register_date,
                    :country,
                    :lang,
                    :os_version,
                    :device_name,
                    :app_version
            );
        ";
        $p = [
            ':user_id' => null,
            ':hive_id' => $user->hiveId,
            ':hive_uid' => $user->hiveUid,
            ':register_date' => Timezone::getNowUTC(),
            ':country' => $user->country,
            ':lang' => $user->lang,
            ':os_version' => $user->osVersion,
            ':device_name' => $user->deviceName,
            ':app_version' => $user->appVersion
        ];
        return DB::runQuery($q, $p);
    }

    /**
     * @param UserDAO $user
     * @return PDOStatement
     * @throws Exception
     */
    public static function insertUserInfo(UserDAO $user)
    {
        $q = "
            INSERT INTO user_info
            VALUE (
                    :user_id,
                    :last_update,
                    :territory_id,
                    :name,
                    :castle_level,
                    :castle_to_level,
                    :upgrade_time,
                    :penalty_finish_time,
                    :auto_generate_manpower,
                    :manpower,
                    :appended_manpower,
                    :tactical_resource,
                    :food_resource,
                    :luxury_resource
            );
        ";
        // TODO: 초기값 기획 데이터 변환
        $p = [
            ':user_id' => $user->userId,
            ':last_update' => Timezone::getNowUTC(),
            ':territory_id' => 0,
            ':name' => null,
            ':castle_level' => 1,
            ':castle_to_level' => 1,
            ':upgrade_time' => null,
            ':penalty_finish_time' => null,
            ':auto_generate_manpower' => true,
            ':manpower' => 10,
            ':appended_manpower' => 0,
            ':tactical_resource' => 0,
            ':food_resource' => 0,
            ':luxury_resource' => 0
        ];
        return DB::runQuery($q, $p);
    }

    public static function insertUserStatistics(UserDAO $user)
    {
        $q = "
            INSERT INTO user_statistic
            VALUE (
                    :user_id,
                    :war_request,
                    :war_victory,
                    :war_defeated,
                    :despoil_defense_success,
                    :despoil_defense_fail,
                    :boss1_kill_count,
                    :boss2_kill_count,
                    :boss3_kill_count
            );
        ";
        // TODO: 초기값 기획 데이터 변환
        $p = [
            ':user_id' => $user->userId,
            ':war_request' => 0,
            ':war_victory' => 0,
            ':war_defeated' => 0,
            ':despoil_defense_success' => 0,
            ':despoil_defense_fail' => 0,
            ':boss1_kill_count' => 0,
            ':boss2_kill_count' => 0,
            ':boss3_kill_count' => 0
        ];
        return DB::runQuery($q, $p);
    }
}
