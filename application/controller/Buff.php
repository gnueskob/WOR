<?php

namespace lsb\App\controller;

use lsb\App\models\BuffDAO;
use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\services\BuffServices;
use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\Plan;
use lsb\Libs\Timezone;

class Buff extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저에게 적용중인 버프 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];

            // 버프 정보 불러오기 전 만료된 버프 삭제
            BuffServices::refreshBuff($userId);

            $buffs = BuffServices::getBuffsByUser($userId);
            $ctx->addBody(['buffs' => Utils::toArrayAll($buffs)]);
            $ctx->send();
        });

        // 버프 추가 (충성도 버프)
        $router->post(
            '/add/:user_id',
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $buffType = $data['buff_type'];

                // 버프 추가 전 만료 버프 삭제
                BuffServices::refreshBuff($userId);

                list($buffClass) = Plan::getBuffClass($buffType);
                $finishUnitTime = Plan::getBuffFinishUnitTime($buffType);

                if ($buffClass === PLAN_BUFF_TYPE_TROPHY) {
                    // 전리품 버프
                    $buffId = BuffServices::createBuff($userId, $buffType, $finishUnitTime);

                    $buffArr = BuffServices::getBuff($buffId)->toArray();
                    $ctx->addBody(['buff' => $buffArr]);
                    $ctx->send();
                } else {
                    // 자원 소모 버프
                    $ctx->next();
                }
            },
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                // 자원 소모 버프
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $buffType = $data['buff_type'];

                list($buffClass) = Plan::getBuffClass($buffType);
                $finishUnitTime = Plan::getBuffFinishUnitTime($buffType);
                list($neededTactical, $neededFood, $neededLuxury) = Plan::getBuffResources($buffType);

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);

                $resourceRatio = 1;
                if ($buffClass === PLAN_BUFF_TYPE_RESOURCE_MANPOWER) {
                    // 자원 소모 인구 비례
                    $resourceRatio = $user->manpower;
                }
                $neededTactical *= $resourceRatio;
                $neededFood *= $resourceRatio;
                $neededLuxury *= $resourceRatio;

                // 필요한 재료를 가지고 있는 지 검사
                UserServices::checkResourceSufficient($user, $neededTactical, $neededFood, $neededLuxury);

                DB::beginTransaction();
                UserServices::useResource($userId, $neededTactical, $neededFood, $neededLuxury);
                $buffId = BuffServices::createBuff($userId, $buffType, $finishUnitTime);
                DB::endTransaction();

                $buffArr = BuffServices::getBuff($buffId)->toArray();
                $ctx->addBody(['buff' => $buffArr]);
                $ctx->send();
            }
        );
    }
}
