<?php

namespace lsb\App\query;

use lsb\App\models\Query;
use lsb\App\models\RaidDAO;

class RaidQuery extends Query
{
    public function __construct()
    {
        parent::__construct(RaidDAO::getColumnMap());
    }

    public static function raid()
    {
        return static::make()->setTable('raid');
    }

    /************************************************************/

    private function whereUserId(int $userId)
    {
        return $this->whereEqual(['userId' => $userId]);
    }

    private function whereRaidId(int $warId)
    {
        return $this->whereEqual(['warId' => $warId]);
    }

    private function whereFinished(string $time)
    {
        return $this->whereLT(['finishTime' => $time]);
    }

    private function whereWarring(string $time)
    {
        return $this->whereGTE(['finishTime' => $time]);
    }

    /************************************************************/

    // SELCET QUERY

    public static function qSelectRaid(RaidDAO $dao)
    {
        return static::raid()
            ->selectQurey()
            ->selectAll()
            ->whereRaidId($dao->raidId);
    }

    public static function qSelectRaidByUser(RaidDAO $dao)
    {
        return static::raid()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId);
    }

    public static function qSelectActiveRaidByUser(RaidDAO $dao)
    {
        return static::raid()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId)
            ->whereWarring($dao->finishTime);
    }

    public static function qSelcetFinishedRaidByUser(RaidDAO $dao)
    {
        return static::raid()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId)
            ->whereFinished($dao->finishTime);
    }

    /************************************************************/

    // INSERT QUERY

    public static function qInsertWar(WarDAO $dao)
    {
        return static::war()
            ->insertQurey()
            ->value([
                'warId' => $dao->warId,
                'userId' => $dao->userId,
                'territoryId' => $dao->territoryId,
                'attack' => $dao->attack,
                'manpower' => $dao->manpower,
                'foodResource' => $dao->foodResource,
                'targetDefense' => $dao->targetDefense,
                'prepareTime' => $dao->prepareTime,
                'finishTime' => $dao->finishTime
            ]);
    }

    /************************************************************/

    // DELETE QUERY

    public static function qDeleteWar(WarDAO $dao)
    {
        return static::war()
            ->deleteQurey()
            ->whereUserId($dao->userId)
            ->whereWarId($dao->warId);
    }
}