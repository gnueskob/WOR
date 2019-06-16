<?php

namespace lsb\App\controller;

use lsb\App\services\AllianceServices;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\UserServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Utils\Utils;

class Alliance extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 동맹 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $alliances = AllianceServices::getAcceptedAlliance($userId);
            $ctx->addBody(['alliances' => Utils::toArrayAll($alliances)]);
            $ctx->send();
        });

        // 동맹 대기 정보
        $router->get('/wating/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $alliances = AllianceServices::getWatingAllianceByUser($userId);
            $ctx->addBody(['alliances' => Utils::toArrayAll($alliances)]);
            $ctx->send();
        });

        // 동맹 요청
        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $friendId = $data['friend_id'];

            $friend = UserServices::getUserInfo($friendId);

            ExploratoinServices::checkUserExploredTerritory($userId, $friend->territoryId);

            $allianceWaitId = AllianceServices::requesetAlliance($userId, $friendId);

            $alliancArr = AllianceServices::getAllianceWait($allianceWaitId)->toArray();
            $ctx->addBody(['alliances' => $alliancArr]);
            $ctx->send();
        });

        // 동맹 수락
        $router->put('/accept/:alliance_wait_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $allianceWaitId = $data['alliance_wait_id'];

            $alliance = AllianceServices::getAllianceWait($allianceWaitId);
            $user = UserServices::getUserInfo($alliance->userId);

            DB::beginTransaction();
            $allianceId = AllianceServices::acceptAlliance($alliance->userId, $alliance->friendId);
            AllianceServices::acceptAlliance($alliance->userId, $alliance->friendId);
            ExploratoinServices::exploreTerritory($alliance->friendId, $user->territoryId, 0, true);
            DB::endTransaction();

            $allianceArr = AllianceServices::getAlliance($allianceId)->toArray();
            $ctx->addBody(['alliances' => $allianceArr]);
            $ctx->send();
        });

        // 동맹 취소
        $router->put('/cancel/:alliance_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $allianceId = $data['alliance_id'];

            $alliance = AllianceServices::getAlliance($allianceId);

            DB::beginTransaction();
            AllianceServices::cancelAlliance($alliance->userId, $alliance->friendId);
            AllianceServices::cancelAlliance($alliance->friendId, $alliance->userId);
            DB::endTransaction();
            $ctx->send();
        });

        // 동맹 요청 거절
        $router->put('/reject/:alliance_wait_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $allianceWaitId = $data['alliance_wait_id'];
            AllianceServices::rejectAllianceWait($allianceWaitId);
            $ctx->send();
        });
    }
}
