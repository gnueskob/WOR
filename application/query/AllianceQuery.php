<?php

namespace lsb\App\query;

use lsb\App\models\AllianceDAO;

class AllianceQuery extends Query
{
    public function __construct()
    {
        parent::__construct(AllianceDAO::getColumnMap());
    }

    public static function alliance()
    {
        return static::make()->setTable('alliance');
    }

    public static function allianceWait()
    {
        return static::make()->setTable('alliance_wait');
    }

    /**************************************************************/

    public function setAccept(string $time)
    {
        return $this->set(['acceptedTime' => $time]);
    }

    public function whereUserId(int $userId)
    {
        return $this->whereEqual(['userId' => $userId]);
    }

    public function whereFriendId(int $friendId)
    {
        return $this->whereEqual(['friendId' => $friendId]);
    }

    public function whereAllianceId(int $allianceId)
    {
        return $this->whereEqual(['allianceId' => $allianceId]);
    }

    /**************************************************************/

    // SELECT QUERY

    public static function qSelectAlliance(AllianceDAO $dao)
    {
        return static::alliance()
            ->selectQurey()
            ->selectAll()
            ->whereAllianceId($dao->allianceId);
    }

    public static function qSelectAllianceWait(AllianceDAO $dao)
    {
        return static::allianceWait()
            ->selectQurey()
            ->selectAll()
            ->whereAllianceId($dao->allianceId);
    }

    public static function qSelectAcceptedAlliances(AllianceDAO $dao)
    {
        return static::alliance()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId);
    }

    public static function qSelectWatingAlliances(AllianceDAO $dao)
    {
        return static::allianceWait()
            ->selectQurey()
            ->selectAll()
            ->whereFriendId($dao->userId);
    }

    public static function qSelectAllianceByUserAndFriend(AllianceDAO $dao)
    {
        return static::alliance()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId)
            ->whereFriendId($dao->friendId);
    }

    /**************************************************************/

    // INSERT QUERY

    public static function qInsertAllianceWait(AllianceDAO $dao)
    {
        return static::allianceWait()
            ->insertQurey()
            ->value([
                'allianceId' => $dao->allianceId,
                'userId' => $dao->userId,
                'friendId' => $dao->friendId,
                'createdTime' => $dao->createdTime
            ]);
    }

    public static function qInsertAlliance(AllianceDAO $dao)
    {
        return static::alliance()
            ->insertQurey()
            ->value([
                'allianceId' => $dao->allianceId,
                'userId' => $dao->userId,
                'friendId' => $dao->friendId,
                'createdTime' => $dao->createdTime
            ]);
    }

    /**************************************************************/

    // DELETE QUERY

    public static function qDeleteAlliance(AllianceDAO $dao)
    {
        return static::alliance()
            ->deleteQurey()
            ->whereUserId($dao->userId)
            ->whereFriendId($dao->friendId);
    }

    public static function qDeleteAllianceWait(AllianceDAO $dao)
    {
        return static::allianceWait()
            ->deleteQurey()
            ->whereAllianceId($dao->allianceId);
    }


}