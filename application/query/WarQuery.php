<?php

namespace lsb\App\query;

use lsb\App\models\WarDAO;

class WarQuery extends Query
{
    public function __construct()
    {
        parent::__construct(WarDAO::getColumnMap());
    }

    public static function war()
    {
        return static::make()->setTable('war');
    }

    /************************************************************/

    private function whereUserId(int $userId)
    {
        return $this->whereEqual(['userId' => $userId]);
    }

    private function whereWarId(int $warId)
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

    // SELECT QUERY

    public static function qSelectWar(WarDAO $dao)
    {
        return static::war()
            ->selectQurey()
            ->selectAll()
            ->whereWarId($dao->warId);
    }

    public static function qSelectWarByUser(WarDAO $dao)
    {
        return static::war()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId);
    }

    public static function qSelectActiveWarByUser(WarDAO $dao)
    {
        return static::war()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId)
            ->whereWarring($dao->finishTime);
    }

    public static function qSelcetFinishedWarByUser(WarDAO $dao)
    {
        return static::war()
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
            ->whereWarId($dao->warId);
    }

    /************************************************************/

    /*
    public static function selectWar(WarDAO $war)
    {
        $q = "
            SELECT *
            FROM war
            WHERE war_id = :war_id;
        ";
        $p = [':war_id' => $war->warId];
        return DB::runQuery($q, $p);
    }

    public static function selectWarByUser(WarDAO $war)
    {
        $q = "
            SELECT *
            FROM war
            WHERE user_id = :user_id;
        ";
        $p = [':user_id' => $war->userId];
        return DB::runQuery($q, $p);
    }

    public static function insertWar(WarDAO $war)
    {
        $q = "
            INSERT INTO war
            VALUE (
                :war_id,
                :user_id,
                :territory_id,
                :attack,
                :manpower,
                :building_list,
                :food_resource,
                :target_defense,
                :prepare_time,
                :finish_time
            );
        ";
        $p = [
            ':war_id' => null,
            ':user_id' => $war->userId,
            ':territory_id' => $war->territoryId,
            ':attack' => $war->attack,
            ':manpower' => $war->manpower,
            ':building_list' => $war->buildingList,
            ':food_resource' => $war->foodResource,
            ':target_defense' => $war->targetDefense,
            ':prepare_time' => $war->prepareTime,
            ':finish_time' => $war->finishTime
        ];
        return DB::runQuery($q, $p);
    }

    public static function deleteWarByUser(WarDAO $war)
    {
        $q = "
            DELETE FROM war
            WHERE user_id = :user_id
              AND finish_time < :finish_time;
        ";
        $p = [
            ':user_id' => $war->userId,
            ':finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }

    public static function deleteWarByTerritory(WarDAO $war)
    {
        $q = "
            DELETE FROM war
            WHERE territoryId = :territoryId
              AND finish_time < :finish_time;
        ";
        $p = [
            ':territoryId' => $war->territoryId,
            ':finish_time' => Timezone::getNowUTC()
        ];
        return DB::runQuery($q, $p);
    }
    */
}
