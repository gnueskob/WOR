<?php


namespace lsb\App\query;

use lsb\App\models\TerritoryDAO;

class TerritoryQuery extends Query
{
    public function __construct()
    {
        parent::__construct(TerritoryDAO::getColumnMap());
    }

    public static function territory()
    {
        return static::make()->setTable('territory');
    }

    /**************************************************************/

    public function whereUserId(int $userId)
    {
        return $this->whereEqual(['userId' => $userId]);
    }

    public function whereTerritoryId(int $territoryId)
    {
        return $this->whereEqual(['territoryId' => $territoryId]);
    }

    public function whereExploreId(int $explopreId)
    {
        return $this->whereEqual(['exploreId' => $explopreId]);
    }

    public function whereExploring(string $time)
    {
        return $this->whereLT(['exploreTime' => $time]);
    }

    /**************************************************************/

    // SELECT QUERY

    public static function qSelectTerritory(TerritoryDAO $dao)
    {
        return static::territory()
            ->selectQurey()
            ->selectAll()
            ->whereExploreId($dao->exploreId);
    }

    public static function qSelectTerritoryByUser(TerritoryDAO $dao)
    {
        return static::territory()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId);
    }

    public static function qSelectTerritoryByUserAndTerritoryId(TerritoryDAO $dao)
    {
        return static::territory()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId)
            ->whereTerritoryId($dao->territoryId);
    }

    public static function qCountExploringTerritoryByUser(TerritoryDAO $dao, string $as = "COUNT")
    {
        return static::territory()
            ->selectQurey()
            ->selectCountAll($as)
            ->whereUserId($dao->userId)
            ->whereExploring($dao->exploreTime);
    }

    /**************************************************************/

    // INSERT QUERY

    public static function qInsertTerritory(TerritoryDAO $dao)
    {
        return static::territory()
            ->insertQurey()
            ->value([
                'exploreId' => $dao->exploreId,
                'territoryId' => $dao->territoryId,
                'userId' => $dao->userId,
                'exploreTime' => $dao->exploreTime
            ]);
    }
}
