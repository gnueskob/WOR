<?php

namespace lsb\App\controller;

use lsb\App\models\UserDAO;
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
            $data = $ctx->req->getParams();
            $userId = $data['user_id'];
            $warArr = WarServices::getWarByUser($userId)->toArray();
            $ctx->addBody(['war' => $warArr]);
        });

        $router->post(
            '/add/:user_id',
            Lock::lockUser(MANPOWER, 2),
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $targetTerritoryId = $data['territory_id'];

                // 유저가 먼저 해당 영토를 탐사 했는가?
                $territory = ExploratoinServices::getTerritoryByUserAndTerritory($userId, $targetTerritoryId);
                CtxException::notExploredYet($territory->isEmpty());
                CtxException::notExploredYet(!$territory->isExplored());

                $user = UserServices::getUserInfo($userId);
                CtxException::invalidId($user->isEmpty());

                // 타겟 영토까지의 거리
                list($userX, $userY) = UserServices::getLocation($user->territoryId);
                list($targetX, $targetY) = UserServices::getLocation($targetTerritoryId);
                $dist = Utils::getManhattanDistance($userX, $userY, $targetX, $targetY);

                // 단위 별 기획 데이터
                $unit = Plan::getDataAll(PLAN_UNIT);
                $warUnitTimeCoefficient = $unit[WAR_UNIT_TIME]['value'];
                $warUnitResourcePerManpower = $unit[WAR_UNIT_RESOURCE]['value'];
                $warPrepareUnitTime = $unit[WAR_PREPARE_TIME]['value'];

                // 기본 출전 준비 시간
                $prepareTime = Timezone::getCompleteTime($warPrepareUnitTime);
                // 출전 준비 시간 + 이동 시간
                $warFinishUnitTime = $warUnitTimeCoefficient * $dist + $warPrepareUnitTime;
                $finishTime = Timezone::getCompleteTime($warFinishUnitTime);

                // 사용하려는 병영에 등록된 총 병력, 공격력
                list($totalManpower, $totalAttack) = BuildingServices::getArmyManpower($userId);
                CtxException::manpowerInsufficient($totalManpower === 0);

                // 유저가 가지고 있는 무기 별 총 공격력
                $weaponAttack = WeaponServices::getAttack($userId);

                // 총 필요한 군량
                $neededFoodResource = $warUnitResourcePerManpower * $totalManpower * $dist;
                CtxException::resourceInsufficient(!$user->hasSUfficientFood($neededFoodResource));

                // 전쟁 출전 준비 시작시의 타겟 영토 건물 기준으로 계산
                $targetDefense = UserServices::getTargetDefense($targetTerritoryId);

                // 전쟁 생성용 컨테이너
                $warContainer = new WarDAO();
                $warContainer->userId = $userId;
                $warContainer->territoryId = $targetTerritoryId;
                $warContainer->attack = $totalAttack + $weaponAttack;
                $warContainer->manpower = $totalManpower;
                $warContainer->foodResource = $neededFoodResource;
                $warContainer->targetDefense = $targetDefense;
                $warContainer->prepareTime = $prepareTime;
                $warContainer->finishTime = $finishTime;

                DB::beginTransaction();
                UserServices
                    ::watchUserId($userId)
                    ::modifyUserResource(0, -$neededFoodResource, 0)
                    ::modifyUserManpower(-$totalManpower, -$totalManpower, 0)
                    ::apply();
                BuildingServices::resetBuildingsManpower();
                $warId = WarServices::createWar($warContainer);
                CtxException::alreadyWarExists($warId === -1);
                MessageServices::postMessage(MSG_WAR_FNS, $userId, $warId, $finishTime);
                DB::endTransaction();

                $warArr = WarServices::getWar($warId)->toArray();
                $ctx->addBody(['war' => $warArr]);
            }
        );

        // 전쟁 완료 확인
        $router->get('/add/:war_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $warId = $data['war_id'];
            $war = WarServices::getWar($warId);

            CtxException::invalidId($war->isEmpty());
            CtxException::notFinishedYet(!$war->isFinished());

            WarServices::resolveWarResult($war);

            $ctx->send();
        });
    }
}
