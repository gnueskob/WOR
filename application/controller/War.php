<?php

namespace lsb\App\controller;

use lsb\App\models\BuildingDAO;
use lsb\App\models\Utils;
use lsb\App\services\BuildingServices;
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
            $territoryId = $data['territory_id'];

            WarServices::refreshWarByUser($userId);
            WarServices::refreshWarByTerritory($territoryId);

            $war = WarServices::selectUserWar($userId);
            $ctx->addBody(['war' => Utils::toArray($war)]);
            $ctx->send();
        });

        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $territoryId = $data['territory_id'];

            WarServices::refreshWarByUser($userId);
            WarServices::refreshWarByTerritory($territoryId);

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
            $takenTime = $unitTime * $timeCoefficient * $l2dist;

            // 인력 소모 필요
            $manpowerSpinlockKey = SpinLock::getKey(MANPOWER, $userId);
            SpinLock::spinLock($manpowerSpinlockKey, 2);

            // 군량 소모 필요
            $resourceSpinlockKey = SpinLock::getKey(RESOURCE, $userId);
            try {
                SpinLock::spinLock($resourceSpinlockKey, 1);
            } catch (Exception $e) {
                SpinLock::spinUnlock($manpowerSpinlockKey);
            }

            $buildings = BuildingServices::getBuildingsByUser($userId);

            // 병영에 등록된 병력
            $totalManpower = 0;
            foreach ($buildings as $building) {
                if ($building->buildingType !== PLAN_BUILDING_ID_ARMY ||
                    is_null($building->deployTime) ||
                    $building->deployTime > Timezone::getNowUTC()) {
                    continue;
                }
                $totalManpower += $building->manpower;
            }

            // 총 필요한 군량
            $neededFoodResource = $unit[WAR_UNIT_RESOURCE] * $totalManpower * $l2dist;

            $user = UserServices::getUserInfo($userId);
            $foodResource = $user->foodResource - $neededFoodResource;
            if ($foodResource < 0) {
                (new CtxException())->resourceInsufficient();
            }

            // TODO: 일단 전쟁 레코드 추가는 하는데... 완료 처리시 병력 어캐?
        });
    }
}
