<?php

namespace lsb\App\controller;

use lsb\App\models\BuildingDAO;
use lsb\App\models\Utils;
use lsb\App\services\BuildingServices;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\UserServices;
use lsb\App\services\WarServices;
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

            $war = WarServices::selectUserWar($userId);
            $ctx->addBody(['war' => Utils::toArray($war)]);
            $ctx->send();
        });

        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $territoryId = $data['territory_id'];
            $armyManpower = $data['manpower'];

            // 해당 영토 탐사 했는지 여부
            $territory = ExploratoinServices::getTerritoryByUserAndTerritory($userId, $territoryId);
            if (is_null($territory) || $territory->exploreTime > Timezone::getNowUTC()) {
                (new CtxException())->notYetExplored();
            }

            // 해당 영토 정보
            $territory = Plan::getData(PLAN_TERRITORY, $territoryId);

            if ($territory['type'] === 0) {
                (new CtxException())->notUsedTerritory();
            }

            // 단위 별 기획 데이터
            $unit = Plan::getDataAll(PLAN_UNIT);

            // 탐사 지점까지의 유클리드 거리, 걸리는 시간 계산
            $centerX = (int) ($unit[TERRITORY_W]['value'] / 2);
            $centerY = (int) ($unit[TERRITORY_H]['value'] / 2);
            $x = $territory['location_x'];
            $y = $territory['location_y'];
            $l2dist = abs($x - $centerX) + abs($y - $centerY);

            $unitTime = $unit[UNIT_TIME]['value'];
            $timeCoefficient = $unit[WAR_UNIT_TIME]['value'];
            $defaultWarPrepareTime = $unit[WAR_PREPARE_TIME]['value'];
            $takenTime = $unitTime * ($defaultWarPrepareTime + $timeCoefficient * $l2dist);
            $prepareTime = (new Timezone())->addDate("{$defaultWarPrepareTime} seconds")->getTime();
            $finishTime = (new Timezone())->addDate("{$takenTime} seconds")->getTime();

            // 병영에 등록된 병력
            $totalManpower = BuildingServices::getArmyManpower($userId, $armyManpower);
            if ($totalManpower === 0) {
                (new CtxException())->manpowerInsufficient();
            }

            // 총 필요한 군량
            $neededFoodResource = $unit[WAR_UNIT_RESOURCE] * $totalManpower * $l2dist;

            $user = UserServices::getUserInfo($userId);
            $foodResource = $user->foodResource - $neededFoodResource;
            if ($foodResource < 0) {
                (new CtxException())->resourceInsufficient();
            }

            // 전쟁 출전 준비 시작시의 타겟 영토 건물 기준으로 계산
            $targetDefense = UserServices::getTargetDefense($territoryId, $finishTime);
        });
    }
}
