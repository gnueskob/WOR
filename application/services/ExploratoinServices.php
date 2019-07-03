<?php

namespace lsb\App\services;

use Exception;
use lsb\App\models\TerritoryDAO;
use lsb\Libs\CtxException as CE;
use lsb\Libs\ErrorCode;
use lsb\Libs\Plan;
use lsb\Utils\Utils;

class ExploratoinServices
{
    public static function getDistanceToTargetTile(int $tileId)
    {
        list($tileX, $tileY) = Plan::getTileLocation($tileId);
        list($centerX, $centerY) = UserServices::getCastleLocation();

        $dist = Utils::getDistance($tileX, $tileY, $centerX, $centerY);
        return $dist;
    }

    public static function getDistanceToTargetTerritory(int $territoryId, int $targetTerritoryId)
    {
        list($userX, $userY) = Plan::getTerritoryLocation($territoryId);
        list($targetX, $targetY) = Plan::getTerritoryLocation($targetTerritoryId);

        $dist = Utils::getDistance($userX, $userY, $targetX, $targetY);
        return $dist;
    }

    /**
     * @param int $userId
     * @param int $territoryId
     * @throws Exception
     */
    public static function checkUserExploredTerritory(int $userId, int $territoryId)
    {
        $territory = TerritoryDAO::getSpecificTerritory($userId, $territoryId);
        CE::check($territory->isEmpty(), ErrorCode::IS_NOT_EXPLORED);
        CE::check(false === $territory->isExplored(), ErrorCode::IS_NOT_EXPLORED);
    }
}
