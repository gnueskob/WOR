<?php

namespace lsb\App\controller;

use lsb\App\models\AllianceDAO;
use lsb\App\models\TerritoryDAO;
use lsb\App\models\UserDAO;
use lsb\App\services\ExploratoinServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException as CE;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Utils\Utils;

class Alliance extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        /*************************************************************************************************************
         * 유저 동맹 정보
         *************************************************************************************************************/
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];

            $alliances = AllianceDAO::getAcceptedAlliance($userId);
            $ctx->addResBody(['alliances' => Utils::toArrayAll($alliances)]);
        });

        /*************************************************************************************************************
         * 동맹 대기 정보
         *************************************************************************************************************/
        $router->get('/waiting/:user_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];

            $alliances = AllianceDAO::getWatingAlliance($userId);
            $ctx->addResBody(['alliances' => Utils::toArrayAll($alliances)]);
        });

        /*************************************************************************************************************
         * 동맹 요청
         *************************************************************************************************************/
        $router->post('/add', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];
            $friendId = $data['friend_id'];

            $friend = UserDAO::getUserInfo($friendId);

            ExploratoinServices::checkUserExploredTerritory($userId, $friend->territoryId);

            $allianceWaitId = AllianceDAO::requestAlliance($userId, $friendId);

            $allianc = AllianceDAO::getAllianceWait($allianceWaitId);
            $ctx->addResBody(['alliances' => $allianc->toArray()]);
        });

        /*************************************************************************************************************
         * 동맹 수락
         *************************************************************************************************************/
        $router->put('/accept', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $allianceWaitId = $data['alliance_wait_id'];

            $allianceWait = AllianceDAO::getAllianceWait($allianceWaitId);
            $user = UserDAO::getUserInfo($allianceWait->userId);

            $territory = TerritoryDAO::getSpecificTerritory($allianceWait->friendId, $user->territoryId);

            DB::beginTransaction();
            $allianceId = AllianceDAO::acceptAlliance($allianceWait->userId, $allianceWait->friendId);
            AllianceDAO::acceptAlliance($allianceWait->friendId, $allianceWait->userId);
            if ($territory->isEmpty()) {
                TerritoryDAO::exploreTerritory($allianceWait->friendId, $user->territoryId, 0);
            } elseif (false === $territory->isExplored()) {
                $territory->finishExplore();
            }
            $allianceWait->resolveAccept();
            DB::endTransaction();

            $alliance = AllianceDAO::getAlliance($allianceId);
            $ctx->addResBody(['alliances' => $alliance->toArray()]);
        });

        /*************************************************************************************************************
         * 동맹 취소
         *************************************************************************************************************/
        $router->put('/cancel', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $allianceId = $data['alliance_id'];

            $alliance = AllianceDAO::getAlliance($allianceId);
            $rAlliance = AllianceDAO::container();
            $rAlliance->userId = $alliance->friendId;
            $rAlliance->friendId = $alliance->userId;

            DB::beginTransaction();
            $alliance->cancel();
            $rAlliance->cancel();
            DB::endTransaction();
        });

        /*************************************************************************************************************
         * 동맹 요청 거절
         *************************************************************************************************************/
        $router->put('/reject', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $allianceWaitId = $data['alliance_wait_id'];

            AllianceDAO::container($allianceWaitId)->reject();
        });
    }
}
