<?php

namespace lsb\App\controller;

use lsb\App\models\AllianceDAO;
use lsb\App\models\BuildingDAO;
use lsb\App\models\UserDAO;
use lsb\App\services\AllianceServices;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Utils\Lock;
use lsb\App\models\WarDAO;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\UserServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException as CE;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;

class War extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        /*************************************************************************************************************
         * 유저 전쟁 정보
         *************************************************************************************************************/
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];

            $war = WarDAO::getWarAboutUser($userId);
            $ctx->addResBody(['war' => $war->toArray()]);
        });

        /*************************************************************************************************************
         * 끝난 전쟁 확인
         *************************************************************************************************************/
        $router->get('/check', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];

            // 만료된 전쟁
            $war = WarDAO::getFinishedWar($userId);
            $ctx->addResBody(['war' => $war->toArray()]);
        });

        /*************************************************************************************************************
         * 전쟁 선포
         *************************************************************************************************************/
        $router->post(
            '/add',
            Lock::lock(MANPOWER, 2),
            Lock::lock(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $userId = $data['user_id'];
                $targetTerritoryId = $data['territory_id'];
                $friendId = $data['friend_id'];

                // 이미 전쟁 중 인가?
                $war = WarDAO::getWarAboutUser($userId);
                CE::check(false === $war->isEmpty() && false === $war->isFinished(), ErrorCode::ALREADY_WARRING);

                // 유저가 먼저 해당 영토를 탐사 했는가?
                ExploratoinServices::checkUserExploredTerritory($userId, $targetTerritoryId);

                // 동맹 지원군을 요청할 시 상대방과 동맹 중인가?
                if (isset($friendId)) {
                    AllianceServices::checkAllianceWithFriend($userId, $friendId);
                }

                $friend = UserDAO::getUserInfo($friendId);
                $user = UserDAO::getUserInfo($userId);
                $targetUser = UserDAO::getTargetUserInfo($targetTerritoryId);

                // 타겟 영토까지의 거리
                $dist = ExploratoinServices::getDistanceToTargetTerritory($user->territoryId, $targetTerritoryId);

                // 단위 별 기획 데이터
                list($prepareUnitTime, $moveUnitTimeCoeff, $resourceCoeff) = Plan::getUnitWar();

                // 출전 준비 시간 + 이동 시간
                $finishUnitTime = $moveUnitTimeCoeff * $dist + $prepareUnitTime;

                list($totalAttackPower, $totalManpower) = UserServices::getTotalAttackAndManpower($user);

                // 총 필요한 군량
                $food = $resourceCoeff * $totalManpower * $dist;
                CE::check(false === $user->hasResource(0, $food, 0), ErrorCode::RESOURCE_INSUFFICIENT);

                // 전쟁 출전 준비 시작시의 타겟 영토 건물 기준으로 계산
                $targetDefense = UserServices::getTotalDefense($targetUser);

                DB::beginTransaction();
                $user
                    ->useResources(0, $food, 0, true)
                    ->useManpower($totalManpower);
                BuildingDAO::container()->resetBuildingsManpower($userId);
                $warId = WarDAO::createWar([
                    'userId' => $userId,
                    'territoryId' => $targetTerritoryId,
                    'attack' => $totalAttackPower,
                    'friendAttack' => $friend->friendAttack,
                    'manpower' => $totalManpower,
                    'foodResource' => $food,
                    'targetDefense' => $targetDefense,
                    'prepareUnitTime' => $prepareUnitTime,
                    'finishUnitTime' => $finishUnitTime
                ]);
                DB::endTransaction();

                $war = WarDAO::getWar($warId);
                $ctx->addResBody(['war' => $war->toArray()]);
            }
        );

        /*************************************************************************************************************
         * 전쟁 완료 확인
         *************************************************************************************************************/
        $router->put(
            '/add',
            Lock::lock(MANPOWER, 2),
            Lock::lock(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $warId = $data['war_id'];

                $war = WarDAO::getWar($warId);
                CE::check(false === $war->isFinished(), ErrorCode::IS_NOT_FINISHED);

                $result = $war->resolveWarResult();
                if (empty($result)) {
                    return;
                }

                list($remainManpower, $remainFood) = $result;

                // TODO: 패널티
                DB::beginTransaction();
                UserDAO::container($war->userId)
                    ->takeManpower($remainManpower, true)
                    ->takeResources(0, $remainFood, 0);
                $war->removeWar();
                DB::endTransaction();

                $user = UserServices::getAllProperty($war->userId);
                $ctx->addResBody(['user' => $user->toArray()]);
            }
        );

        /*************************************************************************************************************
         * 전쟁 출전 취소
         *************************************************************************************************************/
        $router->put(
            '/cancel',
            Lock::lock(MANPOWER, 2),
            Lock::lock(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $warId = $data['war_id'];

                $war = WarDAO::getWar($warId);
                CE::check($war->isPrepared(), ErrorCode::ALREADY_PREPARED);

                $halfManpower = (int) ($war->manpower / 2);
                $halfFood = (int) ($war->foodResource / 2);

                DB::beginTransaction();
                UserDAO::container($war->userId)
                    ->takeManpower($halfManpower, true)
                    ->takeResources(0, $halfFood, 0);
                $war->removeWar();
                DB::endTransaction();

                $user = UserServices::getAllProperty($war->userId);
                $ctx->addResBody(['user' => $user->toArray()]);
            }
        );
    }
}
