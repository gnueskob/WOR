<?php

namespace lsb\App\controller;

use Exception;
use lsb\App\models\Utils;
use lsb\App\services\BuildingServices;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\Plan;
use lsb\Libs\SpinLock;
use lsb\Libs\Timezone;
use PDOException;

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
        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['userId'];
            $tileId = $data['tile_id'];
            $territoryId = $data['$territory_id'];
            $buildingType = $data['building_type'];

            // 해당 위치가 탐사되었는지 검사
            $tile = ExploratoinServices::getTileByUserAndTile($userId, $tileId);
            if (is_null($tile) || $tile->exploreTime > Timezone::getNowUTC()) {
                (new CtxException())->notYetExplored();
            }

            // 건물 생성에 필요한 자원
            $plan = Plan::getData(PLAN_BUILDING, $buildingType);

            // 자원을 확인하고 소모시키는 중간 부분에서 자원량이 갱신되면 안됨
            $spinlockKey = SpinLock::getKey(RESOURCE, $userId);
            SpinLock::spinLock($spinlockKey, 1);

            // 현재 유저 자원 정보
            $user = UserServices::getUserInfo($userId);

            $tacticalResource = $user->tacticalResource - $plan['need_tactical_resource'];
            $foodResource = $user->foodResource - $plan['need_food_resource'];
            $luxuryResource = $user->luxuryResource - $plan['need_luxury_resource'];

            if ($tacticalResource < 0 || $foodResource < 0 || $luxuryResource < 0) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->resourceInsufficient();
            }

            $unitTime = Plan::getData(PLAN_UNIT, UNIT_TIME);
            $neededTime = $plan['create_unit_time'] * $unitTime['value'];
            $creatTime = (new Timezone())->addDate("{$neededTime} seconds")->getTime();

            $db = DB::getInstance()->getDBConnection();
            try {
                $db->beginTransaction();

                $buildingId = BuildingServices::createBuilding(
                    $userId,
                    $tileId,
                    $territoryId,
                    $buildingType,
                    $creatTime
                );

                UserServices::modifyUserResource(
                    $userId,
                    $tacticalResource,
                    $foodResource,
                    $luxuryResource
                );

                if ($db->commit() === false) {
                    (new CtxException())->transactionFail();
                }
            } catch (CtxException | PDOException | Exception $e) {
                $db->rollBack();
                SpinLock::spinUnlock($spinlockKey);
                throw $e;
            }

            SpinLock::spinUnlock($spinlockKey);

            $building = BuildingServices::getBuilding($buildingId);
            Utils::checkNull($building);
            $ctx->addBody(['building' => Utils::toArray($building)]);
            $ctx->send();
        });

        // 유저 빌딩 건설 완료 확인
        $router->get('/add/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $buildingId = $data['building_id'];
            $building = BuildingServices::getBuilding($buildingId);
            Utils::checkNull($building);
            if ($building->createTime > Timezone::getNowUTC()) {
                (new CtxException())->notCompletedYet();
            }
            $ctx->addBody(Utils::toArray($building));
            $ctx->send();
        });

        // 특정 빌딩 업그레이드 요청
        $router->post('/upgrade/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $buildingId = $data['building_id'];

            // 업그레이드 하려는 건물 정보
            $building = BuildingServices::getBuilding($buildingId);
            Utils::checkNull($building);

            // 업그레이드 진행중인지 검사
            if ($building->upgradeTime > Timezone::getNowUTC()) {
                (new CtxException())->notCompletedYet();
            }

            // 빌딩 건물 유형에 따라 업그레이드 기획 정보
            switch ($building['building_type']) {
                default:
                    (new CtxException())->invalidBuildingType();
                case 3:
                    $keyTag = PLAN_UPG_DEF_TOWER;
                    break;
                case 4:
                    $keyTag = PLAN_UPG_ARMY;
                    break;
            }

            // 다음 레벨 업그레이드에 필요한 자원
            $plan = Plan::getData($keyTag, $building->currentLevel);

            // 자원 확인, 소모 사이에 변동이 없어야 함
            $spinlockKey = SpinLock::getKey(RESOURCE, $userId);
            SpinLock::spinLock($spinlockKey, 1);

            // 현재 유저 자원 정보
            $user = UserServices::getUserInfo($userId);

            $tacticalResource = $user->tacticalResource - $plan['need_tactical_resource'];
            $foodResource = $user->foodResource - $plan['need_food_resource'];
            $luxuryResource = $user->luxuryResource - $plan['need_luxury_resource'];

            if ($tacticalResource < 0 || $foodResource < 0 || $luxuryResource < 0) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->resourceInsufficient();
            }

            $unitTime = Plan::getData(PLAN_UNIT, UNIT_TIME);
            $neededTime = $plan['upgrade_unit_time'] * $unitTime['value'];
            $upgradeTime = (new Timezone())->addDate("{$neededTime} seconds")->getTime();

            $db = DB::getInstance()->getDBConnection();
            try {
                $db->beginTransaction();

                BuildingServices::upgradeBuilding($buildingId, $building->currentLevel, $upgradeTime);

                UserServices::modifyUserResource(
                    $userId,
                    $tacticalResource,
                    $foodResource,
                    $luxuryResource
                );

                if ($db->commit() === false) {
                    (new CtxException())->transactionFail();
                }
            } catch (CtxException | PDOException | Exception $e) {
                $db->rollBack();
                SpinLock::spinUnlock($spinlockKey);
                throw $e;
            }
            SpinLock::spinUnlock($spinlockKey);

            $building = BuildingServices::getBuilding($buildingId);
            Utils::checkNull($building);
            $ctx->addBody(['building' => Utils::toArray($building)]);
            $ctx->send();
        });

        // 특정 빌딩 업그레이드 완료 확인
        $router->get('/upgrade/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $buildingId = $data['building_id'];
            $building = BuildingServices::getBuilding($buildingId);
            Utils::checkNull($building);
            if ($building->upgradeTime > Timezone::getNowUTC()) {
                (new CtxException())->notCompletedYet();
            }
            $ctx->addBody(Utils::toArray($building));
            $ctx->send();
        });

        // 건물 인구 배치 요청
        $router->post('/deploy/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $buildingId = $data['building_id'];
            $buildingType = $data['building_type'];
            $manpowerSet = $data['manpower'];

            // 건물 별 인력배치 기획 정보
            $plan = Plan::getData(PLAN_BUILDING, $buildingType);

            // 업그레이드 하려는 건물 정보
            $building = BuildingServices::getBuilding($buildingId);
            Utils::checkNull($building);

            // 건물이 생성 되었는지 검사
            if ($building->createTime <= Timezone::getNowUTC()) {
                (new CtxException())->notYetCreatedBuilding();
            }

            // 투입 인력이 최대값을 초과하는지
            if ($building->manpower + $manpowerSet > $plan['max_manpower']) {
                (new CtxException())->exceedManpowerBuilding();
            }

            // 인력 확인, 소모 사이에 외부에서의 인력 갱신이 있으면 안됨
            $spinlockKey = SpinLock::getKey(MANPOWER, $userId);
            SpinLock::spinLock($spinlockKey, 1);

            // 현재 유저 정보
            $user = UserServices::getUserInfo($userId);

            // 투입 인력만큼의 가용 인력을 보유 중 인지 확인
            if ($user->manpowerAvailable < $manpowerSet) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->manpowerInsufficient();
            }

            $manpowerUsed = $user->manpowerUsed - $manpowerSet;

            $unitTime = Plan::getData(PLAN_UNIT, UNIT_TIME);
            $neededTime = $plan['deploy_unit_time'] * $unitTime['value'];
            $deployTime = (new Timezone())->addDate("{$neededTime} seconds")->getTime();

            $db = DB::getInstance()->getDBConnection();
            try {
                $db->beginTransaction();

                BuildingServices::deployBuilding($buildingId, $manpowerSet, $deployTime);

                UserServices::modifyUserUsedManpower($userId, $manpowerUsed);

                if ($db->commit() === false) {
                    (new CtxException())->transactionFail();
                }
            } catch (CtxException | PDOException | Exception $e) {
                $db->rollBack();
                SpinLock::spinUnlock($spinlockKey);
                throw $e;
            }
            SpinLock::spinUnlock($spinlockKey);

            $building = BuildingServices::getBuilding($buildingId);
            Utils::checkNull($building);
            $ctx->addBody(['building' => Utils::toArray($building)]);
            $ctx->send();
        });

        // 건물 인구배치 완료 확인
        $router->get('/deploy/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $buildingId = $data['building_id'];
            $building = BuildingServices::getBuilding($buildingId);
            Utils::checkNull($building);
            if ($building->deployTime > Timezone::getNowUTC()) {
                (new CtxException())->notCompletedYet();
            }
            $ctx->addBody(Utils::toArray($building));
            $ctx->send();
        });
    }
}
