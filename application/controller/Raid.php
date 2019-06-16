<?php

namespace lsb\App\controller;

use Couchbase\Exception;
use lsb\App\services\AllianceServices;
use lsb\App\services\BuildingServices;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\RaidServices;
use lsb\App\services\UserServices;
use lsb\App\services\WeaponServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;
use lsb\Utils\Lock;

class Raid extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저가 진행 중인 레이드 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->req->getParams();
            $userId = $data['user_id'];
            $raidArr = RaidServices::getRaidByUser($userId)->toArray();
            $ctx->addBody(['raid' => $raidArr]);
            $ctx->send();
        });

        // 끝난 레이드 검사
        $router->get('/check/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];

            $raid = RaidServices::refreshRaid($userId);
            $raidArr = $raid->isEmpty() ? [] : $raid->toArray();

            $ctx->addBody(['raid' => $raidArr]);
            $ctx->send();
        });

        // 레이드 출전
        $router->post(
            '/add/:user_id',
            Lock::lockUser(MANPOWER, 2),
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $targetTerritoryId = $data['territory_id'];

                // 이미 출전 중 인가?
                RaidServices::checkWarring($userId);

                list($territoryClass) = Plan::getTerritoryClass($targetTerritoryId);
                CtxException::notBossTerritory($territoryClass !== PLAN_TERRITORY_TYPE_BOSS);

                // 유저가 먼저 해당 영토를 탐사 했는가?
                ExploratoinServices::checkUserExploredTerritory($userId, $targetTerritoryId);

                $user = UserServices::getUserInfo($userId);

                // 타겟 영토까지의 거리
                $dist = ExploratoinServices::getDistanceToTargetTerritory($user->territoryId, $targetTerritoryId);

                // 단위 별 기획 데이터
                list($prepareUnitTime, $moveUnitTimeCoeff, $resourceCoeff) = Plan::getUnitWar();

                // 출전 준비 시간 + 이동 시간
                $finishUnitTime = $moveUnitTimeCoeff * $dist + $prepareUnitTime;

                // 병영에 등록된 총 병력, 공격력
                list($armyManpower, $armyAttack) = BuildingServices::getArmyManpowerAndAttack($userId);

                // 유저가 가지고 있는 무기 별 총 공격력
                $weaponAttack = WeaponServices::getAttackPower($userId);
                $totalAttackPower = $armyAttack + $weaponAttack;

                // 총 필요한 군량
                $neededFoodResource = $resourceCoeff * $armyManpower * $dist;
                UserServices::checkResourceSufficient($user, 0, $neededFoodResource, 0);

                // TODO: 레이드 보스 체력 계산ㅎ
                DB::beginTransaction();
                UserServices::useManpower($userId, $armyManpower, true);
                UserServices::useResource($userId, 0, $neededFoodResource, 0);
                $warId = WarServices::createWar(
                    $userId,
                    $targetTerritoryId,
                    $totalAttackPower,
                    $friend->friendAttack,
                    $armyManpower,
                    $neededFoodResource,
                    $targetDefense,
                    $prepareUnitTime,
                    $finishUnitTime);
                DB::endTransaction();

                $warArr = WarServices::getWar($warId)->toArray();
                $ctx->addBody(['war' => $warArr]);
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
    }
}