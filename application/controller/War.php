<?php

namespace lsb\App\controller;

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
use lsb\Libs\SpinLock;
use Exception;
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
            $war = WarServices::getWarByUser($userId);
            Utils::throwExceptionIfNull($war);
            $ctx->addBody(['war' => $war->toArray()]);
        });

        $router->post(
            '/add/:user_id',
            Lock::lock(MANPOWER, 2),
            Lock::lock(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $targetTerritoryId = $data['territory_id'];
                $armyManpower = $data['manpower'];

                // 해당 영토가 유저가 사용할 수 있는 영토인가?
                $territory = Plan::getData(PLAN_TERRITORY, $targetTerritoryId);
                if ($territory['type'] === 0) {
                    (new CtxException())->notUsedTerritory();
                }

                // 유저가 먼저 해당 영토를 탐사 했는가?
                $territory = ExploratoinServices::getTerritoryByUserAndTerritory($userId, $targetTerritoryId);
                if (is_null($territory) || $territory->exploreTime > Timezone::getNowUTC()) {
                    (new CtxException())->notYetExplored();
                }

                $user = UserServices::getUserInfo($userId);

                // 탐사 지점까지의 유클리드 거리, 걸리는 시간 계산
                list($userX, $userY) = UserServices::getLocation($user->territoryId);
                list($targetX, $targetY) = UserServices::getLocation($targetTerritoryId);
                $dist = Utils::getManhattanDistance($userX, $userY, $targetX, $targetY);

                // 단위 별 기획 데이터
                $unit = Plan::getDataAll(PLAN_UNIT);
                $unitTime = $unit[UNIT_TIME]['value'];
                $warUnitTimeCoefficient = $unit[WAR_UNIT_TIME]['value'];
                $warUnitResourcePerManpower = $unit[WAR_UNIT_RESOURCE]['value'];
                $warPrepareUnitTime = $unit[WAR_PREPARE_TIME]['value'];

                // 기본 출전 준비 시간
                $prepareTime = $unitTime * $warPrepareUnitTime;
                // 출전 준비 시간 + 이동 시간
                $takenTime = $unitTime * $warUnitTimeCoefficient * $dist + $prepareTime;

                $prepareTime = (new Timezone())->addDate("{$prepareTime} seconds")->getTime();
                $finishTime = (new Timezone())->addDate("{$takenTime} seconds")->getTime();

                // 사용하려는 병영에 등록된 총 병력, 공격력
                list($totalManpower, $totalAttack) = BuildingServices::getArmyManpower($userId, $armyManpower);
                if ($totalManpower === 0) {
                    (new CtxException())->manpowerInsufficient();
                }

                // 총 필요한 군량
                $neededFoodResource = $warUnitResourcePerManpower * $totalManpower * $dist;
                $foodResource = $user->foodResource - $neededFoodResource;
                if ($foodResource < 0) {
                    (new CtxException())->resourceInsufficient();
                }

                // 전쟁 출전 준비 시작시의 타겟 영토 건물 기준으로 계산
                $targetDefense = UserServices::getTargetDefense($targetTerritoryId, $finishTime);

                // 유저가 가지고 있는 무기 별 총 공격력
                $weaponAttack = WeaponServices::getAttack($userId);

                $warContainer = new WarDAO();
                $warContainer->userId = $userId;
                $warContainer->territoryId = $targetTerritoryId;
                $warContainer->attack = $totalAttack + $weaponAttack;
                $warContainer->manpower = $totalManpower;
                $warContainer->buildingList = json_decode($armyManpower);
                $warContainer->foodResource = $neededFoodResource;
                $warContainer->targetDefense = $targetDefense;
                $warContainer->prepareTime = $prepareTime;
                $warContainer->finishTime = $finishTime;

                DB::beginTransaction();
                UserServices::modifyUserResource(
                    $userId,
                    $user->tacticalResource,
                    $foodResource,
                    $user->luxuryResource
                );
                $warId = WarServices::prepareWar($warContainer);
                DB::endTransaction();

                $war = WarServices::getWar($warId);
                Utils::throwExceptionIfNull($war);
                $ctx->addBody(['war' => $war->toArray()]);
            }
        );
    }
}
