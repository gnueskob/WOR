<?php

namespace lsb\App\controller;

use lsb\App\models\WeaponDAO;
use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\services\WeaponServices;
use lsb\App\services\UserServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;
use lsb\Libs\Timezone;

class Weapon extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저 보유 무기들 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $weapons = WeaponServices::getWeaponsByUser($userId);
            $ctx->addBody(['weapons' => Utils::toArrayAll($weapons)]);
            $ctx->send();
        });

        // 무기 제작 요청
        $router->post(
            '/add/:user_id',
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $weaponType = $data['weapon_type'];

                // 건물 생성에 필요한 자원
                $plan = Plan::getData(PLAN_WEAPON, $weaponType);
                $createUnitTime = $plan['create_unit_time'];
                $neededTactical = $plan['need_tactical_resource'];
                $neededFood = $plan['need_food_resource'];
                $neededLuxury = $plan['need_luxury_resource'];

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);
                CtxException::invalidId($user->isEmpty());

                // 필요한 재료를 가지고 있는 지 검사
                $hasResource = $user->hasSufficientResource($neededTactical, $neededFood, $neededLuxury);
                CtxException::resourceInsufficient(!$hasResource);

                $creatTime = Timezone::getCompleteTime($createUnitTime);

                $weaponContainer = new WeaponDAO();
                $weaponContainer->userId = $userId;
                $weaponContainer->weaponType = $weaponType;
                $weaponContainer->createTime = $creatTime;

                DB::beginTransaction();
                $weaponId = WeaponServices::createWeapon($weaponContainer);
                UserServices
                    ::watchUserId($userId)
                    ::modifyUserResource(-$neededTactical, -$neededFood, -$neededLuxury)
                    ::apply();
                DB::endTransaction();

                $weaponArr = WeaponServices::getWeapon($weaponId)->toArray();
                $ctx->addBody(['weapon' => $weaponArr]);
                $ctx->send();
            }
        );

        // 무기 제작 완료 확인
        $router->get('/add/:weapon_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $weaponId = $data['weapon_id'];
            $weapon = WeaponServices::getWeapon($weaponId);

            CtxException::invalidId($weapon->isEmpty());
            CtxException::notCreatedYet(!$weapon->isCreated());

            $ctx->addBody(['weapon' => $weapon->toArray()]);
            $ctx->send();
        });

        // 무기 업그레이드 요청
        $router->post(
            '/upgrade/:weapon_id',
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $weaponId = $data['weapon_id'];

                // 업그레이드 하려는 무기 정보
                $weapon = WeaponServices::getWeapon($weaponId);
                CtxException::invalidId($weapon->isEmpty());

                // 다음 레벨 업그레이드에 필요한 자원
                $plan = Plan::getData(PLAN_UPG_WEAPON, $weapon->currentLevel);
                $upgradeUnitTime = $plan['upgrade_unit_time'];
                $neededTactical = $plan['need_tactical_resource'];
                $neededFood = $plan['need_food_resource'];
                $neededLuxury = $plan['need_luxury_resource'];

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);
                CtxException::invalidId($user->isEmpty());

                // 필요한 재료를 가지고 있는 지 검사
                $hasResource = $user->hasSufficientResource($neededTactical, $neededFood, $neededLuxury);
                CtxException::resourceInsufficient(!$hasResource);

                // 업그레이드 완료 시간
                $upgradeTime = Timezone::getCompleteTime($upgradeUnitTime);

                DB::beginTransaction();
                WeaponServices
                    ::watchWeaponId($weaponId)
                    ::upgradeWeapon($weapon->currentLevel, $upgradeTime)
                    ::apply(true);
                UserServices
                    ::watchUserId($userId)
                    ::modifyUserResource(-$neededTactical, -$neededFood, -$neededLuxury)
                    ::apply();
                DB::endTransaction();

                $weaponArr = WeaponServices::getWeapon($weaponId)->toArray();
                $ctx->addBody(['weapon' => $weaponArr]);
                $ctx->send();
            }
        );

        // 무기 업그레이드 완료 확인
        $router->get('/upgrade/:weapon_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $weaponId = $data['weapon_id'];
            $weapon = WeaponServices::getWeapon($weaponId);

            CtxException::invalidId($weapon->isEmpty());
            CtxException::notUpgradedYet(!$weapon->isUpgraded());

            $ctx->addBody(['weapon' => $weapon->toArray()]);
            $ctx->send();
        });
    }
}
