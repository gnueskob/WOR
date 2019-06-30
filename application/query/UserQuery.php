<?php

namespace lsb\App\query;

use lsb\App\models\UserDAO;

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

    private function setResource($tactical, $food, $luxury, $sign)
    {
        if ($sign === '+') {
            return $this->setAdd([
                'tacticalResource' => $tactical,
                'foodResource' => $food,
                'luxuryResource' => $luxury
            ]);
        } elseif ($sign === '-') {
            return $this->setSub([
                'tacticalResource' => $tactical,
                'foodResource' => $food,
                'luxuryResource' => $luxury
            ]);
        }
    }

    public function setSubResource($tactical, $food, $luxury)
    {
        return $this->setResource($tactical, $food, $luxury, '-');
    }

    public function setAddResource($tactical, $food, $luxury)
    {
        return $this->setResource($tactical, $food, $luxury, '+');
    }

    private function setManpower($manpower, $sign)
    {
        if ($sign === '+') {
            return $this->setAdd(['manpower' => $manpower]);
        } elseif ($sign === '-') {
            return $this->setSub(['manpower' => $manpower]);
        }
    }

    public function setSubManpower($manpower)
    {
        return $this->setManpower($manpower, '-');
    }

    public function setAddManpower($manpower)
    {
        return $this->setManpower($manpower, '+');
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

    // SELCET QUERY FOR USER

    public static function qSelectHiveUser(UserDAO $dao)
    {
        return static::userPlatform()
            ->selectQurey()
            ->select(['userId'])
            ->whereHiveUser($dao->hiveId, $dao->hiveUid);
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

    /**************************************************************/

    // UPDATE QUERY FOR USER

    public static function qSetLastVisitFromUserInfo(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->set(['lastVisit' => $dao->lastVisit])
            ->whereUserId($dao->userId);
    }

    public static function qSetNameFromUserInfo(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->set(['name' => $dao->name])
            ->whereUserId($dao->userId);
    }

    public static function qSetTerritoryIdFromUserInfo(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->set(['territoryId' => $dao->territoryId])
            ->whereUserId($dao->userId);
    }

    public static function qSubtarctResourcesFromUserInfo(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->setSubResource(
                $dao->tacticalResource,
                $dao->foodResource,
                $dao->luxuryResource
            )
            ->whereUserId($dao->userId);
    }

    public static function qAddResourcesFromUserInfo(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->setAddResource(
                $dao->tacticalResource,
                $dao->foodResource,
                $dao->luxuryResource
            )
            ->whereUserId($dao->userId);
    }

    public static function qSubtractManpowerFromUserInfo(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->setSubManpower($dao->manpower)
            ->whereUserId($dao->userId);
    }

    public static function qAddManpowerFromUserInfo(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->setAddManpower($dao->manpower)
            ->whereUserId($dao->userId);
    }

    public static function qUpdateUserInfoSetCastle(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->set([
                'castleLevel' => $dao->castleLevel,
                'castleToLevel' => $dao->castleToLevel,
                'upgradeTime' => $dao->upgradeTime
            ])
            ->whereUserId($dao->userId);
    }

    public static function qSetFriendAttack(UserDAO $dao)
    {
        return static::userInfo()
            ->updateQurey()
            ->set(['friendAttack' => $dao->friendAttack])
            ->whereUserId($dao->userId);
    }

    /**************************************************************/

    // INSERT QUERY FOR USER

    public static function qInsertUserPlatform(UserDAO $dao)
    {
        return static::userPlatform()
            ->insertQurey()
            ->value([
                'userId' => $dao->userId,
                'hiveId' => $dao->hiveId,
                'hiveUid' => $dao->hiveUid,
                'registerDate' => $dao->registerDate,
                'country' => $dao->country,
                'lang' => $dao->lang,
                'osVersion' => $dao->osVersion,
                'appVersion' => $dao->appVersion
            ]);
    }

    public static function qInsertUserInfo(UserDAO $dao)
    {
        return static::userInfo()
            ->insertQurey()
            ->value([
                'userId' => $dao->userId,
                'lastVisit' => $dao->lastVisit,
                'territoryId' => $dao->territoryId,
                'name' => $dao->name,
                'castleLevel' => $dao->castleLevel,
                'castleToLevel' => $dao->castleToLevel,
                'upgradeTime' => $dao->upgradeTime,
                'penaltyFinishTime' => $dao->penaltyFinishTime,
                'autoGenerateManpower' => $dao->autoGenerateManpower,
                'manpower' => $dao->manpower,
                'appendedManpower' => $dao->appendedManpower,
                'tacticalResource' => $dao->tacticalResource,
                'foodResource' => $dao->foodResource,
                'luxuryResource' => $dao->luxuryResource
            ]);
    }

    public static function qInsertUserStat(UserDAO $dao)
    {
        return static::userStat()
            ->insertQurey()
            ->value([
                'userId' => $dao->userId,
                'warRequest' => $dao->warRequest,
                'warVictory' => $dao->warVictory,
                'warDefeated' => $dao->warDefeated,
                'despoilDefenseSuccess' => $dao->despoilDefenseSuccess,
                'despoilDefenseFail' => $dao->despoilDefenseFail,
                'boss1KillCount' => $dao->boss1KillCount,
                'boss2KillCount' => $dao->boss2KillCount,
                'boss3KillCount' => $dao->boss3KillCount
            ]);
    }

    /**************************************************************/

    public static function jSelectUserFromAll(UserDAO $dao)
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
}
