<?php

namespace lsb\App\controller;

use lsb\App\models\BuildingDAO;
use lsb\App\models\TileDAO;
use lsb\App\models\UserDAO;
use lsb\Libs\CtxException as CE;
use lsb\Libs\ErrorCode;
use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\services\UserServices;
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

        /*************************************************************************************************************
         * 유저 건물들 정보
         *************************************************************************************************************/
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];
            $buildings = BuildingDAO::getBuildings($userId);
            $ctx->addResBody(['buildings' => Utils::toArrayAll($buildings)]);
        });

        /*************************************************************************************************************
         * 건물 생성
         *************************************************************************************************************/
        $router->post(
            '/add',
            // 자원을 확인하고 소모시키는 중간 부분에서 자원량이 갱신되면 안됨
            Lock::lockUser(MANPOWER, 2),
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $userId = $data['user_id'];
                $tileId = $data['tile_id'];
                $buildingType = $data['building_type'];

                $tile = TileDAO::getSpecificTile($userId, $tileId);

                // 해당 위치가 탐사되었는지 검사
                CE::check($tile->isEmpty(), ErrorCode::IS_NOT_EXPLORED);
                CE::check(false === $tile->isExplored(), ErrorCode::IS_NOT_EXPLORED);

                // 건물 기획 데이터
                list($tatical, $food, $luxury) = Plan::getBuildingCreateResources($buildingType);
                list($createUnitTime) = Plan::getBuildingUnitTime($buildingType);
                list($createManpower) = Plan::getBuildingManpower($buildingType);

                // 현재 유저 정보
                $user = UserServices::getUserInfoWithManpower($userId);

                CE::check(false === $user->hasResource($tatical, $food, $luxury), ErrorCode::RESOURCE_INSUFFICIENT);
                CE::check(false === $user->hasAvailableManpower($createManpower), ErrorCode::MANPOWER_INSUFFICIENT);

                DB::beginTransaction();
                $user->useResources($tatical, $food, $luxury);
                $buildingId = BuildingDAO::createBuilding([
                    'userId' => $user->userId,
                    'territoryId' => $user->territoryId,
                    'tileId' => $tileId,
                    'buildingType' => $buildingType,
                    'createUnitTime' => $createUnitTime
                ]);
                DB::endTransaction();

                $building = BuildingDAO::getBuilding($buildingId);
                $ctx->addResBody(['building' => $building->toArray()]);
            }
        );

        /*************************************************************************************************************
         * 건물 생성 완료 확인
         *************************************************************************************************************/
        $router->get('/add', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $buildingId = $data['building_id'];

            $building = BuildingDAO::getBuilding($buildingId);
            CE::check(false === $building->isCreated(), ErrorCode::IS_NOT_CREATED);

            $ctx->addResBody(['building' => $building->toArray()]);
        });

        /*************************************************************************************************************
         * 건물 업그레이드 요청
         *************************************************************************************************************/
        $router->put(
            '/upgrade',
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $buildingId = $data['building_id'];

                $building = BuildingDAO::getBuilding($buildingId);
                $user = UserDAO::getUserInfo($building->userId);

                // 다음 레벨 업그레이드에 필요한 자원
                list($upgradable, $maxLevel) = Plan::getBuildingUpgradeStatus($building->buildingType);
                list($tatical, $food, $luxury)
                    = Plan::getBuildingUpgradeResources($building->buildingType, $building->currentLevel);
                list(, $upgradeUnitTime,) = Plan::getBuildingUnitTime($building->buildingType, $building->currentLevel);

                // 건물이 생성 되었는지, 업그레이드 가능 상태인지 검사
                CE::check(false === $building->isCreated(), ErrorCode::IS_NOT_CREATED);
                CE::check($building->isUpgrading(), ErrorCode::IS_UPGRADING);
                CE::check(false === $upgradable, ErrorCode::NOT_UPGRADABLE);
                CE::check($building->currentLevel >= $maxLevel, ErrorCode::MAX_LEVEL);

                // 필요한 재료를 가지고 있는 지 검사
                CE::check($user->hasResource($tatical, $food, $luxury), ErrorCode::RESOURCE_INSUFFICIENT);

                DB::beginTransaction();
                $user->useResources($tatical, $food, $luxury);
                $building->upgrade($upgradeUnitTime);
                DB::endTransaction();

                $building = BuildingDAO::getBuilding($building->buildingId);
                $ctx->addResBody(['building' => $building->toArray()]);
            }
        );

        /*************************************************************************************************************
         * 건물 업그레이드 완료 확인
         *************************************************************************************************************/
        $router->get('/upgrade', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $buildingId = $data['building_id'];

            $building = BuildingDAO::getBuilding($buildingId);
            CE::check(false === $building->isUpgraded(), ErrorCode::IS_NOT_UPGRADED);

            $ctx->addResBody(['building' => $building->toArray()]);
        });

        /*************************************************************************************************************
         * 건물 인구 배치 요청
         *************************************************************************************************************/
        $router->put(
            '/deploy',
            // 인력 확인, 소모 사이에 외부에서의 인력 갱신이 있으면 안됨
            Lock::lockUser(MANPOWER),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $buildingId = $data['building_id'];
                $manpower = $data['manpower'];

                $building = BuildingDAO::getBuilding($buildingId);
                $user = UserDAO::getUserInfo($building->userId);

                // 건물이 생성 되었는지, 인구 배치 가능 상태 인지 검사
                CE::check(false === $building->isCreated(), ErrorCode::IS_NOT_CREATED);
                CE::check($building->isDeploying(), ErrorCode::IS_DEPLOYING);

                // 건물 별 인력배치 기획 정보
                list(, $minManpower, $maxManpower)
                    = Plan::getBuildingManpower($building->buildingType, $building->currentLevel);
                list(, , $deployUnitTime) = Plan::getBuildingUnitTime($building->buildingType);

                // 투입 인력이 건물 배치 최소 인력보다 많은지, 최대 인력을 초과하는지 검사
                CE::check($building->manpower + $manpower < $minManpower, ErrorCode::INSUFFICIENT_MINMANPOWER);
                CE::check($building->manpower + $manpower > $maxManpower, ErrorCode::EXCEED_MAXMANPOWER);

                // 유저 가용 인력이 충분한지 검사
                CE::check($user->hasAvailableManpower($manpower), ErrorCode::MANPOWER_INSUFFICIENT);

                $building->deploy($manpower, $deployUnitTime);

                $building = BuildingDAO::getBuilding($building->buildingId);
                $ctx->addResBody(['building' => $building->toArray()]);
            }
        );

        /*************************************************************************************************************
         * 건물 인구 배치 완료 확인
         *************************************************************************************************************/
        $router->get('/deploy', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $buildingId = $data['building_id'];

            $building = BuildingDAO::getBuilding($buildingId);
            CE::check(false === $building->isDeployed(), ErrorCode::IS_NOT_DEPLOYED);

            $ctx->addResBody(['building' => $building->toArray()]);
        });

        /*************************************************************************************************************
         * 건물 인구 배치 취소
         *************************************************************************************************************/
        $router->put(
            '/delete',
            Lock::lockUser(MANPOWER),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $buildingId = $data['building_id'];

                BuildingDAO::container($buildingId)->cancelDeploy();

                $building = BuildingDAO::getBuilding($buildingId);
                $ctx->addResBody(['building' => $building->toArray()]);
            }
        );
    }
}
