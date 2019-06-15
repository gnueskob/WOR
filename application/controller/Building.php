<?php

namespace lsb\App\controller;

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

class Building extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저 빌딩 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $buildings = BuildingServices::getBuildingsByUser($userId);
            $ctx->addBody(['buildings' => Utils::toArrayAll($buildings)]);
            $ctx->res->send();
        });

        // 유저 빌딩 건설 요청
        $router->post(
            '/add/:user_id',
            // 자원을 확인하고 소모시키는 중간 부분에서 자원량이 갱신되면 안됨
            Lock::lockUser(MANPOWER, 2),
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $tileId = $data['tile_id'];
                $buildingType = $data['building_type'];

                // TODO:
                // 해당 위치가 탐사되었는지 검사
                $tile = ExploratoinServices::getTileByUserAndTile($userId, $tileId);
                CtxException::notExploredYet($tile->isEmpty());
                CtxException::notExploredYet(!$tile->isExplored());

                // 건물 기획 데이터
                list($neededTactical, $neededFood, $neededLuxury) = Plan::getBuildingResource($buildingType);
                list($createUnitTime) = Plan::getBuildingUnitTime($buildingType);
                list($neededManpower) = Plan::getBuildingManpower($buildingType);

                // 현재 유저 정보
                $user = UserServices::getUserInfoWithManpower($userId);

                UserServices::checkResourceSufficient($user, $neededTactical, $neededFood, $neededLuxury);
                UserServices::checkAvailableManpowerSufficient($user, $neededManpower);

                DB::beginTransaction();
                UserServices::useResource($user->userId, $neededTactical, $neededFood, $neededLuxury);
                $buildingId = BuildingServices::create($user->userId, $user->territoryId, $tileId, $buildingType, $createUnitTime);
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
            BuildingServices::checkCreateFinished($building);

            $ctx->addBody(['building' => $building->toArray()]);
            $ctx->send();
        });

        // 특정 빌딩 업그레이드 요청
        $router->put(
            '/upgrade/:building_id',
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $buildingId = $data['building_id'];

                $building = BuildingServices::getBuilding($buildingId);

                // 건물이 생성 되었는지, 업그레이드 가능 상태인지 검사
                BuildingServices::checkCreateFinished($building);
                BuildingServices::checkUpgradeStatus($building);
                BuildingServices::checkUpgradableType($building);

                // 다음 레벨 업그레이드에 필요한 자원
                list($neededTactical, $neededFood, $neededLuxury) = Plan::getBuildingUpgradeResource($building->buildingType, $building->currentLevel);
                list(, $upgradeUnitTime,) = Plan::getBuildingUnitTime($building->buildingType);

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);

                // 필요한 재료를 가지고 있는 지 검사
                UserServices::checkResourceSufficient($user, $neededTactical, $neededFood, $neededLuxury);

                DB::beginTransaction();
                UserServices::useResource($user->userId, $neededTactical, $neededFood, $neededLuxury);
                BuildingServices::upgrade($building->buildingId, $building->currentLevel, $upgradeUnitTime);
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
            BuildingServices::checkUpgradeFinished($building);

            $ctx->addBody(['building' => $building->toArray()]);
            $ctx->send();
        });

        // 건물 인구 배치 요청
        $router->put(
            '/deploy/:building_id',
            // 인력 확인, 소모 사이에 외부에서의 인력 갱신이 있으면 안됨
            Lock::lockUser(MANPOWER),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $buildingId = $data['building_id'];
                $deployManpower = $data['manpower'];

                $building = BuildingServices::getBuilding($buildingId);

                // 건물이 생성 되었는지, 인구 배치 가능 상태 인지 검사
                BuildingServices::checkCreateFinished($building);
                BuildingServices::checkDeplpoyStatus($building);

                // 건물 별 인력배치 기획 정보
                list(, $manpowerLimit) = $building->buildingType === PLAN_BUILDING_ID_ARMY
                    ? Plan::getArmyUpgrade($building->currentLevel)
                    : Plan::getBuildingManpower($building->buildingType);
                list(, , $deployUnitTime) = Plan::getBuildingUnitTime($building->buildingType);

                // 투입 인력이 건물 배치 인력 최대값을 초과하는지
                BuildingServices::checkBuildingManpowerOver($building, $deployManpower, $manpowerLimit);

                // 현재 유저 정보
                $user = UserServices::getUserInfoWithManpower($userId);

                // 유저 가용 인력이 충분한지 검사
                UserServices::checkAvailableManpowerSufficient($user, $deployManpower);

                BuildingServices::deploy($building->buildingId, $deployManpower, $deployUnitTime);

                $buildingArr = BuildingServices::getBuilding($buildingId)->toArray();
                $ctx->addBody(['building' => $buildingArr]);
                $ctx->send();
            }
        );

        // 건물 인구 배치 완료 확인
        $router->get('/deploy/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $buildingId = $data['building_id'];

            $building = BuildingServices::getBuilding($buildingId);
            BuildingServices::checkDeployeFinished($building);

            $ctx->addBody(['building' => $building->toArray()]);
            $ctx->send();
        });

        // 건물 인구 배치 취소
        $router->put(
            '/delete/:building_id',
            Lock::lockUser(MANPOWER),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $buildingId = $data['building_id'];

                BuildingServices::cancelDeploy($buildingId);

                $buildingArr = BuildingServices::getBuilding($buildingId)->toArray();
                $ctx->addBody(['building' => $buildingArr]);
                $ctx->send();

            }
        );
    }
}
