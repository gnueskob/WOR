<?php

namespace lsb\App\controller;

use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\services\WeaponServices;
use lsb\App\services\UserServices;
use lsb\Libs\Context;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;

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

                // 무기 생성에 필요한 자원
                list($neededTactical, $neededFood, $neededLuxury) = Plan::getWeaponCreateResources($weaponType);
                list($createUnitTime) = Plan::getWeaponUnitTime($weaponType);

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);

                UserServices::checkResourceSufficient($user, $neededTactical, $neededFood, $neededLuxury);

                DB::beginTransaction();
                UserServices::useResource($userId, $neededTactical, $neededFood, $neededLuxury);
                $weaponId = WeaponServices::createWeapon($userId, $weaponType, $createUnitTime);
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
            WeaponServices::checkCreateFinished($weapon);

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

                WeaponServices::checkCreateFinished($weapon);
                WeaponServices::checkUpgradeStatus($weapon);

                // 다음 레벨 업그레이드에 필요한 자원
                list($neededTactical, $neededFood, $neededLuxury) = Plan::getWeaponUpgradeResources($weapon->weaponType, $weapon->currentLevel);
                list(, $upgradeUnitTime) = Plan::getWeaponUnitTime($weapon->weaponType, $weapon->currentLevel);

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);

                // 필요한 재료를 가지고 있는 지 검사
                UserServices::checkResourceSufficient($user, $neededTactical, $neededFood, $neededLuxury);

                DB::beginTransaction();
                UserServices::useResource($userId, $neededTactical, $neededFood, $neededLuxury);
                WeaponServices::upgradeWeapon($weaponId, $weapon->currentLevel, $upgradeUnitTime);
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
            WeaponServices::checkUpgradeFinished($weapon);

            $ctx->addBody(['weapon' => $weapon->toArray()]);
            $ctx->send();
        });
    }
}
