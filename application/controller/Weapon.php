<?php

namespace lsb\App\controller;

use Exception;
use lsb\App\models\Utils;
use lsb\App\services\WeaponServices;
use lsb\App\services\UserServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;
use lsb\Libs\SpinLock;
use lsb\Libs\Timezone;
use PDOException;

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
        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $weaponType = $data['weapon_type'];

            // 건물 생성에 필요한 자원
            $plan = Plan::getData(PLAN_WEAPON, $weaponType);

            // 자원을 확인하고 소모시키는 중간 부분에서 자원량이 갱신되면 안됨
            $spinlockKey = SpinLock::getKey(RESOURCE, $userId);
            SpinLock::spinLock($spinlockKey, 1);

            // 현재 유저 자원 정보
            $user = UserServices::getUserInfo($userId);
            Utils::checkNull($user);

            $tacticalResource = $user->tacticalResource - $plan['need_tactical_resource'];
            $foodResource = $user->foodResource - $plan['need_food_resource'];
            $luxuryResource = $user->luxuryResource - $plan['need_luxury_resource'];

            if ($tacticalResource < 0 || $foodResource < 0 || $luxuryResource < 0) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->resourceInsufficient();
            }

            $unitTime = Plan::getData(PLAN_UNIT, UNIT_TIME);
            $neededTime = $plan['create_unit_time'] * $unitTime;
            $creatTime = (new Timezone())->addDate("{$neededTime} seconds");

            $db = DB::getInstance()->getDBConnection();
            try {
                $db->beginTransaction();

                UserServices::modifyUserResource(
                    $userId,
                    $tacticalResource,
                    $foodResource,
                    $luxuryResource
                );

                $weaponId = WeaponServices::createWeapon($userId, $weaponType, $creatTime);

                if ($db->commit() === false) {
                    (new CtxException())->transactionFail();
                }
            } catch (CtxException | PDOException | Exception $e) {
                $db->rollBack();
                SpinLock::spinUnlock($spinlockKey);
                throw $e;
            }

            SpinLock::spinUnlock($spinlockKey);

            $weapon = WeaponServices::getWeapon($weaponId);
            Utils::checkNull($weapon);
            $ctx->addBody(['weapon' => Utils::toArray($weapon)]);
            $ctx->send();
        });

        // 무기 제작 완료 확인
        $router->get('/add/:weapon_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $weaponId = $data['weapon_id'];
            $weapon = WeaponServices::getWeapon($weaponId);
            Utils::checkNull($weapon);
            if ($weapon->createTime > Timezone::getNowUTC()) {
                (new CtxException())->notCompletedYet();
            }
            $ctx->addBody(['weapon' => Utils::toArray($weapon)]);
            $ctx->send();
        });

        // 무기 업그레이드 요청
        $router->post('/upgrade/:weapon_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $weaponId = $data['weapon_id'];

            // 업그레이드 하려는 무기 정보
            $weapon = WeaponServices::getWeapon($weaponId);
            Utils::checkNull($weapon);

            // 다음 레벨 업그레이드에 필요한 자원
            $plan = Plan::getData(PLAN_UPG_WEAPON, $weapon->currentLevel);

            // 자원 확인, 소모 사이에 변동이 없어야 함
            $spinlockKey = SpinLock::getKey(RESOURCE, $userId);
            SpinLock::spinLock($spinlockKey, 1);

            // 현재 유저 자원 정보
            $user = UserServices::getUserInfo($userId);
            Utils::checkNull($user);

            $tacticalResource = $user->tacticalResource - $plan['need_tactical_resource'];
            $foodResource = $user->foodResource - $plan['need_food_resource'];
            $luxuryResource = $user->luxuryResource - $plan['need_luxury_resource'];

            if ($tacticalResource < 0 || $foodResource < 0 || $luxuryResource < 0) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->resourceInsufficient();
            }

            $unitTime = Plan::getData(PLAN_UNIT, UNIT_TIME);
            $neededTime = $plan['upgrade_unit_time'] * $unitTime;
            $upgradeTime = (new Timezone())->addDate("{$neededTime} seconds");

            $db = DB::getInstance()->getDBConnection();
            try {
                $db->beginTransaction();

                UserServices::modifyUserResource(
                    $userId,
                    $tacticalResource,
                    $foodResource,
                    $luxuryResource
                );

                WeaponServices::upgradeWeapon($weaponId, $weapon->currentLevel, $upgradeTime);

                if ($db->commit() === false) {
                    (new CtxException())->transactionFail();
                }
            } catch (CtxException | PDOException | Exception $e) {
                $db->rollBack();
                SpinLock::spinUnlock($spinlockKey);
                throw $e;
            }
            SpinLock::spinUnlock($spinlockKey);

            $weapon = WeaponServices::getWeapon($weaponId);
            Utils::checkNull($weapon);
            $ctx->addBody(['weapon' => Utils::toArray($weapon)]);
            $ctx->send();
        });

        // 무기 업그레이드 완료 확인
        $router->put('/upgrade/:weapon_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $weaponId = $data['weapon_id'];
            $weapon = WeaponServices::getWeapon($weaponId);
            Utils::checkNull($weapon);
            if ($weapon->upgradeTime > Timezone::getNowUTC()) {
                (new CtxException())->notCompletedYet();
            }
            $ctx->addBody(['weapon' => Utils::toArray($weapon)]);
            $ctx->send();
        });
    }
}
