<?php

namespace lsb\App\controller;

use Exception;
use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\services\BuildingServices;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;

class Building extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저 빌딩 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['userId'];
            $buildings = BuildingServices::getBuildingsByUser($userId);
            $ctx->addBody(['building' => Utils::toArrayAll($buildings)]);
            $ctx->res->send();
        });

        // 유저 빌딩 건설 요청
        $router->post(
            '/add/:user_id',
            // 자원을 확인하고 소모시키는 중간 부분에서 자원량이 갱신되면 안됨
            Lock::lock(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['userId'];
                $tileId = $data['tile_id'];
                $territoryId = $data['$territory_id'];
                $buildingType = $data['building_type'];

                // 해당 위치가 탐사되었는지 검사
                $tile = ExploratoinServices::getTileByUserAndTile($userId, $tileId);
                CtxException::notYetExplored(!$tile->isExplored());

                // 건물 생성에 필요한 자원
                $plan = Plan::getData(PLAN_BUILDING, $buildingType);
                $createUnitTime = $plan['create_unit_time'];
                $neededTactical = $plan['need_tactical_resource'];
                $neededFood = $plan['need_food_resource'];
                $neededLuxury = $plan['need_luxury_resource'];

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);
                CtxException::invaildUser($user->isEmpty());

                // 필요한 재료를 가지고 있는 지 검사
                $hasResource = $user->hasSufficientResource($neededTactical, $neededFood, $neededLuxury);
                CtxException::resourceInsufficient(!$hasResource);

                // 건설 완료 시간
                $creatTime = Timezone::getCompleteTime($createUnitTime);

                DB::beginTransaction();
                $buildingId = BuildingServices::createBuilding(
                    $userId,
                    $tileId,
                    $territoryId,
                    $buildingType,
                    $creatTime
                );
                // 이미 사용 중인 타일
                CtxException::alreadyUsedTile($buildingId === -1);

                UserServices::modifyUserResource(
                    $user,
                    $neededTactical,
                    $neededFood,
                    $neededLuxury
                );
                DB::endTransaction();

                $buildingArr = BuildingServices::getBuilding($buildingId)->toArray();
                $ctx->addBody(['building' => $buildingArr]);
                $ctx->send();
            }
        );

        // 유저 빌딩 건설 완료 확인
        $router->get('/add/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $buildingId = $data['building_id'];
            $building = BuildingServices::getBuilding($buildingId);

            CtxException::invalidId($building->isEmpty());
            CtxException::notCompletedYet(!$building->isCreated());

            $ctx->addBody(['building' => $building->toArray()]);
            $ctx->send();
        });

        // 특정 빌딩 업그레이드 요청
        $router->post(
            '/upgrade/:building_id',
            Lock::lock(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $buildingId = $data['building_id'];

                // 업그레이드 하려는 건물 정보
                $building = BuildingServices::getBuilding($buildingId);
                CtxException::invalidId($building->isEmpty());

                // 건물이 생성 되었는지 검사
                CtxException::notCompletedYet($building->isCreated());

                // 이미 업그레이드 중 인가?
                CtxException::notCompletedYet($building->isUpgrading());

                // 빌딩 건물 유형에 따라 업그레이드 기획 정보
                switch ($building['building_type']) {
                    default:
                        CtxException::invalidBuildingType();
                    case PLAN_BUILDING_ID_TOWER:
                        $keyTag = PLAN_UPG_DEF_TOWER;
                        break;
                    case PLAN_BUILDING_ID_ARMY:
                        $keyTag = PLAN_UPG_ARMY;
                        break;
                }

                // 다음 레벨 업그레이드에 필요한 자원
                $plan = Plan::getData($keyTag, $building->currentLevel);
                $upgradeUnitTime = $plan['upgrade_unit_time'];
                $neededTactical = $plan['need_tactical_resource'];
                $neededFood = $plan['need_food_resource'];
                $neededLuxury = $plan['need_luxury_resource'];

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);

                // 필요한 재료를 가지고 있는 지 검사
                $hasResource = $user->hasSufficientResource($neededTactical, $neededFood, $neededLuxury);
                CtxException::resourceInsufficient(!$hasResource);

                // 업그레이드 완료 시간
                $upgradeTime = Timezone::getCompleteTime($upgradeUnitTime);

                DB::beginTransaction();
                BuildingServices::upgradeBuilding($buildingId, $building->currentLevel, $upgradeTime);

                UserServices::modifyUserResource(
                    $user,
                    $neededTactical,
                    $neededFood,
                    $neededLuxury
                );
                DB::endTransaction();

                $buildingArr = BuildingServices::getBuilding($buildingId)->toArray();
                $ctx->addBody(['building' => $buildingArr]);
                $ctx->send();
            }
        );

        // 특정 빌딩 업그레이드 완료 확인
        $router->get('/upgrade/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $buildingId = $data['building_id'];
            $building = BuildingServices::getBuilding($buildingId);

            CtxException::invalidId($building->isEmpty());
            CtxException::notCompletedYet(!$building->isUpgraded());

            $ctx->addBody(['building' => $building->toArray()]);
            $ctx->send();
        });

        // 건물 인구 배치 요청
        $router->post(
            '/deploy/:building_id',
            // 인력 확인, 소모 사이에 외부에서의 인력 갱신이 있으면 안됨
            Lock::lock(MANPOWER),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $buildingId = $data['building_id'];
                $buildingType = $data['building_type'];
                $manpowerSet = $data['manpower'];

                // 건물 별 인력배치 기획 정보
                $plan = Plan::getData(PLAN_BUILDING, $buildingType);
                $maxManpower = $plan['max_manpower'];
                $deployUnitTime = $plan['deploy_unit_time'];

                // 업그레이드 하려는 건물 정보
                $building = BuildingServices::getBuilding($buildingId);
                CtxException::invalidId($building->isEmpty());

                // 건물이 생성 되었는가?
                CtxException::notCompletedYet($building->isCreated());

                // 이미 인구 배치 중 인가?
                CtxException::notCompletedYet($building->isDeploying());

                // 투입 인력이 최대값을 초과하는지
                $isOver = $building->manpower + $manpowerSet > $maxManpower;
                CtxException::exceedManpowerBuilding($isOver);

                // 현재 유저 정보
                $user = UserServices::getUserInfo($userId);

                // 투입 인력만큼의 가용 인력을 보유 중 인지 확인
                $hasManpower = $user->manpowerAvailable < $manpowerSet;
                CtxException::manpowerInsufficient($hasManpower);

                $manpowerUsed = $user->manpowerUsed - $manpowerSet;

                $unitTime = Plan::getData(PLAN_UNIT, UNIT_TIME)['value'];
                $neededTime = $deployUnitTime * $unitTime;
                $deployTime = Timezone::getCompleteTime($neededTime);

                DB::beginTransaction();
                // TODO: 서비스 파라미터 어떻게?
                BuildingServices::deployBuilding($buildingId, $manpowerSet, $deployTime);

                UserServices::modifyUserUsedManpower($userId, $manpowerUsed);
                DB::endTransaction();

                $buildingArr = BuildingServices::getBuilding($buildingId)->toArray();
                $ctx->addBody(['building' => $buildingArr]);
                $ctx->send();
            }
        );

        // 건물 인구배치 완료 확인
        $router->get('/deploy/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $buildingId = $data['building_id'];
            $building = BuildingServices::getBuilding($buildingId);

            CtxException::invalidId($building->isEmpty());
            CtxException::notCompletedYet(!$building->isDeployed());

            $ctx->addBody(['building' => $building->toArray()]);
            $ctx->send();
        });
    }
}
