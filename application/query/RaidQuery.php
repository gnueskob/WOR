<?php

namespace lsb\App\query;

use lsb\App\models\BossDAO;
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

    public static function boss()
    {
        return static::make()->setTable('raid_boss');
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

    private function whereTerritory(int $territoryId)
    {
        return $this->whereEqual(['territoryId' => $territoryId]);
    }

    private function whereBossId(int $bossId)
    {
        return $this->whereEqual(['bossId' => $bossId]);
    }

    /************************************************************/

    // SELECT QUERY

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

    public static function qSelectBossByTerritory(BossDAO $dao)
    {
        return static::boss()
            ->selectQurey()
            ->selectAll()
            ->whereTerritory($dao->territoryId);
    }

    /************************************************************/

    // INSERT QUERY

    public static function qInsertRaid(RaidDAO $dao)
    {
        return static::raid()
            ->insertQurey()
            ->value([
                'raidId' => $dao->raidId,
                'bossId' => $dao->bossId,
                'userId' => $dao->userId,
                'territoryId' => $dao->territoryId,
                'bossType' => $dao->bossType,
                'isVictory' => $dao->isVictory,
                'finishTime' => $dao->finishTime
            ]);
    }

    /************************************************************/

    // UPDATE QUERY

    public static function qSubtractBossHP(BossDAO $dao)
    {
        return static::boss()
            ->updateQurey()
            ->setSub(['hitPoint' => $dao->hitPoint])
            ->whereBossId($dao->bossId);
    }

    public static function qStartBossAttack(BossDAO $dao)
    {
        return static::boss()
            ->updateQurey()
            ->set([
                'userId' => $dao->userId,
                'finishTime' => $dao->finishTime,
            ])
            ->whereBossId($dao->bossId);
    }

    public static function qSetVictory(RaidDAO $dao)
    {
        return static::raid()
            ->updateQurey()
            ->set(['isVictory' => $dao->isVictory])
            ->whereBossId($dao->bossId);
    }

    /************************************************************/

    // DELETE QUERY

    public static function qDeleteRaid(RaidDAO $dao)
    {
        return static::raid()
            ->deleteQurey()
            ->whereRaidId($dao->raidId);
    }

    public static function qDeleteBoss(BossDAO $dao)
    {
        return static::boss()
            ->deleteQurey()
            ->whereBossId($dao->bossId);
    }
}