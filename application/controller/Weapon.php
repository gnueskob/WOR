<?php

namespace lsb\App\controller;

use lsb\App\services\WeaponServices;
use lsb\App\services\UserServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;
use lsb\Libs\SpinLock;
use lsb\Libs\Timezone;

class Weapon extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저 보유 무기들 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $res = WeaponServices::getWeaponsByUser($data);
            $ctx->addBody($res);
            $ctx->send();
        });

        // 무기 제작 요청
        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            // 건물 생성에 필요한 자원
            $plan = Plan::getData(PLAN_WEAPON, $data['weapon_type']);

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
            $weaponId = WeaponServices::createWeapon($data);
            SpinLock::spinUnlock($spinlockKey);

            $data['$weapon_id'] = $weaponId;
            $ctx->addBody(WeaponServices::getWeapon($data));
            $ctx->send();
        });

        // 무기 제작 완료 요청
        $router->put('/add/:weapon_id', function (Context $ctx) {
            $data = $ctx->getBody();
            WeaponServices::resolveCreateBuilding($data);
            $ctx->addBody(WeaponServices::getWeapon($data));
            $ctx->send();
        });

        // 무기 업그레이드 요청
        $router->post('/upgrade/:weapon_id', function (Context $ctx) {
            $data = $ctx->getBody();

            // 업그레이드 하려는 건물 정보
            $weapon = WeaponServices::getWeapon($data);

            // 다음 레벨 업그레이드에 필요한 자원
            $plan = Plan::getData(PLAN_UPG_WEAPON, $weapon['upgrade']);

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
            $data['from_level'] = $weapon['upgrade'];
            $data['to_level'] = $weapon['upgrade'] + 1;
            $data['upgrade_finish_time'] = (new Timezone())->addDate('600 seconds');
            WeaponServices::upgradeWeapon($data);
            SpinLock::spinUnlock($spinlockKey);

            $ctx->addBody(WeaponServices::getWeapon($data));
            $ctx->send();
        });

        // 무기 업그레이드 완료 요청
        $router->put('/upgrade/:weapon_id', function (Context $ctx) {
            $data = $ctx->getBody();
            WeaponServices::resolveUpgradeBuilding($data);
            $ctx->addBody(WeaponServices::getWeapon($data));
            $ctx->send();
        });
    }
}
