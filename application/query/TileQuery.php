<?php

namespace lsb\App\query;

use lsb\App\models\Query;
use lsb\App\models\TileDAO;

class TileQuery extends Query
{
    public function __construct()
    {
        parent::__construct(TileDAO::getColumnMap());
    }

    public static function tile()
    {
        return static::make()->setTable('tile');
    }

    /**************************************************************/

    public function whereUserId(int $userId)
    {
        return $this->whereEqual(['userId' => $userId]);
    }

    public function whereTileId(int $tileId)
    {
        return $this->whereEqual(['tileId' => $tileId]);
    }

    public function whereExploreId(int $explopreId)
    {
        return $this->whereEqual(['explopreId' => $explopreId]);
    }

    /**************************************************************/

    // SELECT QUERY

    public static function qSelectTile(TileDAO $dao)
    {
        return static::tile()
            ->selectQurey()
            ->selectAll()
            ->whereExploreId($dao->exploreId);
    }

    public static function qSelectTileByUser(TileDAO $dao)
    {
        return static::tile()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId);
    }

    public static function qSelectTileByUserAndTileId(TileDAO $dao)
    {
        return static::tile()
            ->selectQurey()
            ->selectAll()
            ->whereUserId($dao->userId)
            ->whereTileId($dao->tileId);
    }

    /**************************************************************/

    // INSERT QUERY

    public static function qInsertTile(TileDAO $dao)
    {
        return static::tile()
            ->insertQurey()
            ->value([
                'exploreId' => $dao->exploreId,
                'tileId' => $dao->tileId,
                'userId' => $dao->userId,
                'exploreTime' => $dao->exploreTime
            ]);
    }
}