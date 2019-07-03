<?php

namespace lsb\App\controller;

use lsb\App\models\UserDAO;
use lsb\App\models\WeaponDAO;
use lsb\Libs\CtxException as CE;
use lsb\Libs\ErrorCode;
use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\services\WeaponServices;
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

        /*************************************************************************************************************
         * 유저 무기들 정보
         *************************************************************************************************************/
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];

            $weapons = WeaponDAO::getWeapons($userId);
            $ctx->addResBody(['weapons' => Utils::toArrayAll($weapons)]);
        });

        /*************************************************************************************************************
         * 무기 제작 요청
         *************************************************************************************************************/
        $router->post(
            '/add',
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $userId = $data['user_id'];
                $weaponType = $data['weapon_type'];

                // 무기 생성에 필요한 자원
                list($tatical, $food, $luxury) = Plan::getWeaponCreateResources($data['weapon_type']);
                list($createUnitTime) = Plan::getWeaponUnitTime($weaponType);

                $user = UserDAO::getUserInfo($userId);

                CE::check(false === $user->hasResource($tatical, $food, $luxury), ErrorCode::RESOURCE_INSUFFICIENT);

                DB::beginTransaction();
                $user->useResources($tatical, $food, $luxury);
                $weaponId = WeaponDAO::createWeapon($user->userId, $weaponType, $createUnitTime);
                DB::endTransaction();

                $weapon = WeaponDAO::getWeapon($weaponId);
                $ctx->addResBody(['weapon' => $weapon->toArray()]);
            }
        );

        /*************************************************************************************************************
         * 무기 제작 완료 확인
         *************************************************************************************************************/
        $router->get('/add', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $weaponId = $data['weapon_id'];

            $weapon = WeaponDAO::getWeapon($weaponId);
            CE::check(!$weapon->isCreated(), ErrorCode::IS_NOT_CREATED);

            $ctx->addResBody(['weapon' => $weapon->toArray()]);
        });

        /*************************************************************************************************************
         * 무기 업그레이드 요청
         *************************************************************************************************************/
        $router->post(
            '/upgrade',
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $weaponId = $data['weapon_id'];

                $weapon = WeaponDAO::getWeapon($weaponId);
                $user = UserDAO::getUserInfo($weapon->userId);

                // 다음 레벨 업그레이드에 필요한 자원
                list($tatical, $food, $luxury)
                    = Plan::getWeaponUpgradeResources($weapon->weaponType, $weapon->currentLevel);
                list(, $upgradeUnitTime) = Plan::getWeaponUnitTime($weapon->weaponType, $weapon->currentLevel);

                CE::check(!$weapon->isCreated(), ErrorCode::IS_NOT_CREATED);
                CE::check($weapon->isUpgrading(), ErrorCode::IS_UPGRADING);
                CE::check(false === $user->hasResource($tatical, $food, $luxury), ErrorCode::RESOURCE_INSUFFICIENT);

                DB::beginTransaction();
                $user->useResources($tatical, $food, $luxury);
                $weapon->upgrade($upgradeUnitTime);
                DB::endTransaction();

                $weapon = WeaponDAO::getWeapon($weapon->weaponId);
                $ctx->addResBody(['weapon' => $weapon->toArray()]);
            }
        );

        /*************************************************************************************************************
         * 무기 업그레이드 확인 요청
         *************************************************************************************************************/
        $router->get('/upgrade', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $weaponId = $data['weapon_id'];

            $weapon = WeaponDAO::getWeapon($weaponId);
            CE::check(!$weapon->isUpgraded(), ErrorCode::IS_NOT_UPGRADED);

            $ctx->addResBody(['weapon' => $weapon->toArray()]);
        });
    }
}
