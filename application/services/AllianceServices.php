<?php

namespace lsb\App\services;

use lsb\App\models\AllianceDAO;
use lsb\Libs\CtxException as CE;
use lsb\Libs\ErrorCode;
use Exception;

class AllianceServices
{
    /**
     * @param int $userId
     * @param int $friendId
     * @throws Exception
     */
    public static function checkAllianceWithFriend(int $userId, int $friendId)
    {
        $alliance = AllianceDAO::getAllianceFriend($userId, $friendId);
        CE::check($alliance->isEmpty(), ErrorCode::NOT_ALLIANCE);
    }
}
