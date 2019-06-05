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
            $ctx->addBody($res);
            $ctx->res->send();
        });

        // 유저 빌딩 건설 요청
        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();

            // 건물 생성에 필요한 자원
            $plan = Plan::getData(PLAN_BUILDING, $data['building_type']);

            // 자원을 확인하고 소모시키는 중간 부분에서 자원량이 갱신되면 안됨
            $spinlockKey = SpinLock::getKey(RESOURCE, $data['user_id']);
            SpinLock::spinLock($spinlockKey, 1);

            // 현재 유저 자원 정보
            $user = UserServices::getUserInfo($data);

            if ($plan['need_tactical_resource'] > $user['tactical_resource'] ||
                $plan['need_food_resource'] > $user['food_resource'] ||
                $plan['need_luxury_resource'] > $user['luxury_resource']) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->resourceInsufficient();
            }

            $data['need_tactical_resource'] = (-1) * $plan['need_tactical_resource'];
            $data['need_food_resource'] = (-1) * $plan['need_food_resource'];
            $data['need_luxury_resource'] = (-1) * $plan['need_luxury_resource'];
            $data['create_finish_time'] = (new Timezone())->addDate('600 seconds');
            $buildingId = BuildingServices::createBuilding($data);
            SpinLock::spinUnlock($spinlockKey);

            $data['building_id'] = $buildingId;
            $ctx->addBody(BuildingServices::getBuilding($data));
            $ctx->send();
        });

        // 유저 빌딩 건설 완료 요청
        $router->put('/add/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            BuildingServices::resolveCreateBuilding($data);
            $ctx->addBody(BuildingServices::getBuilding($data));
            $ctx->send();
        });

        // 특정 빌딩 업그레이드 요청
        $router->post('/upgrade/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();

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

            // 다음 레벨 업그레이드에 필요한 자원
            $plan = Plan::getData($keyTag, $building['upgrade']);

            // 자원 확인, 소모 사이에 변동이 없어야 함
            $spinlockKey = SpinLock::getKey(RESOURCE, $data['user_id']);
            SpinLock::spinLock($spinlockKey, 1);

            // 현재 유저 자원 정보
            $user = UserServices::getUserInfo($data);

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
            $data['upgrade_finish_time'] = (new Timezone())->addDate('600 seconds');
            BuildingServices::upgradeBuilding($data);
            SpinLock::spinUnlock($spinlockKey);

            $ctx->addBody(BuildingServices::getBuilding($data));
            $ctx->send();
        });

        // 특정 빌딩 업그레이드 완료 요청
        $router->put('/upgrade/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            BuildingServices::resolveUpgradeBuilding($data);
            $ctx->addBody(BuildingServices::getBuilding($data));
            $ctx->send();
        });

        // 건물 인구 배치 요청
        $router->post('/deploy/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();

            // 건물 별 인력배치 기획 정보
            $plan = Plan::getData(PLAN_BUILDING, $data['building_type']);

            // 인력 확인, 소모 사이에 외부에서의 인력 갱신이 있으면 안됨
            $spinlockKey = SpinLock::getKey(MANPOWER, $data['user_id']);
            SpinLock::spinLock($spinlockKey, 1);

            // 업그레이드 하려는 건물 정보
            $building = BuildingServices::getBuilding($data);

            // 건물이 생성 되었는지 검사
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
            $data['deploy_finish_time'] = (new Timezone())->addDate('150 seconds');

            BuildingServices::deployBuilding($data);
            SpinLock::spinUnlock($spinlockKey);

            $ctx->addBody(BuildingServices::getBuilding($data));
            $ctx->send();
        });

        // 건물 인구배치 완료
        $router->put('/deploy/:building_id', function (Context $ctx) {
            $data = $ctx->getBody();
            BuildingServices::resolveDeployBuilding($data);
            $ctx->addBody(BuildingServices::getBuilding($data));
            $ctx->send();
        });
    }
}
