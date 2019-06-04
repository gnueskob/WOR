<?php

namespace lsb\App\controller;

use lsb\App\services\BuildingServices;
use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\Plan;
use lsb\Libs\SpinLock;
use lsb\Libs\Timezone;

class Building extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저 빌딩 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $res = BuildingServices::getBuildingsByUser($data);
            $ctx->res->body = $res;
            $ctx->res->send();
        });

        // 유저 빌딩 추가
        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();

            $spinlockKey = "resource::{$data['user_id']}";
            SpinLock::spinLock($spinlockKey, 1);

            // 지으려는 건물 정보

            // 현재 유저 자원 정보
            $user = UserServices::getUserInfo($data);

            // 건물 생성에 필요한 자원
            $keyTag = PLAN_BUILDING;
            $plan = Plan::getData($keyTag, $data['building_type']);

            if ($plan['need_tactical_resource'] > $user['tactical_resource'] ||
                $plan['need_food_resource'] > $user['food_resource'] ||
                $plan['need_luxury_resource'] > $user['luxury_resource']) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->resourceInsufficient();
            }

            $data['need_tactical_resource'] = (-1) * $plan['need_tactical_resource'];
            $data['need_food_resource'] = (-1) * $plan['need_food_resource'];
            $data['need_luxury_resource'] = (-1) * $plan['need_luxury_resource'];
            $data['finish_time'] = (new Timezone())->addDate('600 seconds');
            $buildingId = BuildingServices::createBuilding($data);
            SpinLock::spinUnlock($spinlockKey);

            $data['building_id'] = $buildingId;
            $ctx->res->body = BuildingServices::getBuilding($data);
            $ctx->res->send();
        });

        // 유저 빌딩 건설 완료
        $router->put('/add/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $data['create_time'] = Timezone::getNowUTC();
            BuildingServices::resolveCreateBuilding($data);
            $ctx->res->body = BuildingServices::getBuilding($data);
            $ctx->res->send();
        });

        // 특정 빌딩 업그레이드 요청
        $router->post('/upgrade/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();

            $spinlockKey = "resource::{$data['user_id']}";
            SpinLock::spinLock($spinlockKey, 1);

            // 업그레이드 하려는 건물 정보
            $building = BuildingServices::getBuilding($data);

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

            // 현재 유저 자원 정보
            $user = UserServices::getUserInfo($data);

            // 다음 레벨 업그레이드에 필요한 자원
            $plan = Plan::getData($keyTag, $building['upgrade']);

            if ($plan['need_tactical_resource'] > $user['tactical_resource'] ||
                $plan['need_food_resource'] > $user['food_resource'] ||
                $plan['need_luxury_resource'] > $user['luxury_resource']) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->resourceInsufficient();
            }

            $data['need_tactical_resource'] = (-1) * $plan['need_tactical_resource'];
            $data['need_food_resource'] = (-1) * $plan['need_food_resource'];
            $data['need_luxury_resource'] = (-1) * $plan['need_luxury_resource'];
            $data['from_level'] = $building['upgrade'];
            $data['to_level'] = $building['upgrade'] + 1;
            $data['finish_time'] = (new Timezone())->addDate('600 seconds');
            BuildingServices::upgradeBuilding($data);
            SpinLock::spinUnlock($spinlockKey);

            $ctx->res->body = BuildingServices::getBuilding($data);
            $ctx->res->send();
        });

        // 특정 빌딩 업그레이드 완료 요청
        $router->put('/upgrade/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $data['finish_time'] = Timezone::getNowUTC();
            BuildingServices::resolveUpgradeBuilding($data);
            $ctx->res->body = BuildingServices::getBuilding($data);
            $ctx->res->send();
        });

        // 건물 인구 배치
        $router->post('/deploy/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();

            $plan = Plan::getData(PLAN_BUILDING, $data['building_type']);

            $spinlockKey = "manpower::{$data['user_id']}";
            SpinLock::spinLock($spinlockKey, 1);

            // 업그레이드 하려는 건물 정보
            $building = BuildingServices::getBuilding($data);

            // 건물이 생성 되었는지
            if ($building['create_finish_time'] === null) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->notYetCreatedBuilding();
            }
            // 투입 인력이 최대값을 초과하는지
            if ($building['manpower'] + $data['manpower'] > $plan['max_manpower']) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->exceedManpowerBuilding();
            }

            // 현재 유저 정보
            $user = UserServices::getUserInfo($data);

            // 투입 인력만큼의 인력을 보유 중 인지 확인
            $availableManpower = $user['manpower'] - $user['manpower_used'];
            if ($availableManpower < $data['manpower']) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->manpowerInsufficient();
            }

            $data['manpower_used'] = $data['manpower'];
            $data['finish_time'] = (new Timezone())->addDate('150 seconds');

            BuildingServices::deployBuilding($data);
            SpinLock::spinUnlock($spinlockKey);

            $ctx->res->body = BuildingServices::getBuilding($data);
            $ctx->res->send();
        });

        // 건물 인구배치 완료
        $router->put('/deploy/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $data['finish_time'] = Timezone::getNowUTC();
            BuildingServices::resolveDeployBuilding($data);
            $ctx->res->body = BuildingServices::getBuilding($data);
            $ctx->res->send();
        });
    }
}
