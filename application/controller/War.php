<?php

namespace lsb\App\controller;

use lsb\App\models\UserDAO;
use lsb\App\services\AllianceServices;
use lsb\App\services\BuffServices;
use lsb\App\services\MessageServices;
use lsb\Libs\DB;
use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\models\WarDAO;
use lsb\App\services\BuildingServices;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\UserServices;
use lsb\App\services\WarServices;
use lsb\App\services\WeaponServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;
use lsb\Libs\Timezone;

class War extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저가 진행 중인 전쟁 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];

            // 만료된 전쟁
            WarServices::refreshWar($userId);

            $warArr = WarServices::getWarByUser($userId)->toArray();
            $ctx->addBody(['war' => $warArr]);
            $ctx->send();
        });

        // 끝난 전쟁 확인
        $router->get('/check/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];

            // 만료된 전쟁
            $war = WarServices::refreshWar($userId);
            $warArr = $war->isEmpty() ? [] : $war->toArray();

            $ctx->addBody(['war' => $warArr]);
            $ctx->send();
        });

        $router->post(
            '/add/:user_id',
            Lock::lockUser(MANPOWER, 2),
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $targetTerritoryId = $data['territory_id'];
                $friendId = $data['friend_id'];

                // 이미 전쟁 중 인가?
                WarServices::checkWarring($userId);

                // 유저가 먼저 해당 영토를 탐사 했는가?
                ExploratoinServices::checkUserExploredTerritory($userId, $targetTerritoryId);

                // 동맹 지원군을 요청할 시 상대방과 동맹 중인가?
                if (isset($friendId)) {
                    AllianceServices::checkAllianceWithFriend($userId, $friendId);
                }

                $friend = UserServices::getUserInfo($friendId);
                $user = UserServices::getUserInfo($userId);
                $targetUser = UserServices::getUserInfoByTerritory($targetTerritoryId);

                // 타겟 영토까지의 거리
                $dist = ExploratoinServices::getDistanceToTargetTerritory($user->territoryId, $targetTerritoryId);

                // 단위 별 기획 데이터
                list($prepareUnitTime, $moveUnitTimeCoeff, $resourceCoeff) = Plan::getUnitWar();

                // 출전 준비 시간 + 이동 시간
                $finishUnitTime = $moveUnitTimeCoeff * $dist + $prepareUnitTime;

                list($totalAttackPower, $totalManpower) = UserServices::getTotalAttackAndManpower($user);

                // 총 필요한 군량
                $neededFoodResource = $resourceCoeff * $totalManpower * $dist;
                UserServices::checkResourceSufficient($user, 0, $neededFoodResource, 0);

                // 전쟁 출전 준비 시작시의 타겟 영토 건물 기준으로 계산
                $targetDefense = UserServices::getTotalDefense($targetUser);

                DB::beginTransaction();
                UserServices::useManpower($userId, $totalManpower, true);
                UserServices::useResource($userId, 0, $neededFoodResource, 0);
                BuildingServices::resetBuildingsManpower($userId);
                $warId = WarServices::createWar(
                    $userId,
                    $targetTerritoryId,
                    $totalAttackPower,
                    $friend->friendAttack,
                    $totalManpower,
                    $neededFoodResource,
                    $targetDefense,
                    $prepareUnitTime,
                    $finishUnitTime
                );
                DB::endTransaction();

                $warArr = WarServices::getWar($warId)->toArray();
                $ctx->addBody(['war' => $warArr]);
                $ctx->send();
            }
        );

        // 전쟁 완료 확인
        $router->put(
            '/add/:war_id',
            Lock::lockUser(MANPOWER, 2),
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $warId = $data['war_id'];

                $war = WarServices::getWar($warId);
                WarServices::checkFinished($war);

                DB::beginTransaction();
                WarServices::resolveWarResult($war);
                WarServices::removeWar($war->warId);
                DB::endTransaction();

                $userArr = UserServices::getUser($war->warId)->toArray();
                $ctx->addBody(['user' => $userArr]);
                $ctx->send();
            }
        );

        // 전쟁 출전 취소
        $router->put(
            '/cancel/:war_id',
            Lock::lockUser(MANPOWER, 2),
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $warId = $data['war_id'];

                $war = WarServices::getWar($warId);
                WarServices::checkPrepared($war);

                $halfManpower = (int) ($war->manpower / 2);
                $halfFood = (int) ($war->foodResource / 2);

                DB::beginTransaction();
                UserServices::obtainManpower($war->userId, $halfManpower, true);
                UserServices::obtainResource($war->userId, 0, $halfFood, 0);
                WarServices::removeWar($war->warId);
                DB::endTransaction();

                $userArr = UserServices::getUser($war->userId)->toArray();
                $ctx->addBody(['user' => $userArr]);
                $ctx->send();
            }
        );
    }
}
