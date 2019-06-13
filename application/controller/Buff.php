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

                $plan = Plan::getData(PLAN_BUFF, $buffType);
                $planBuffType = $plan['type'];
                $finishUnitTime = $plan['default_finish_time'];

                if ($planBuffType === PLAN_BUFF_TYPE_TROPHY) {
                    // 전리품 버프
                    $buffContainer = new BuffDAO();
                    $buffContainer->userId = $userId;
                    $buffContainer->buffType = $buffType;
                    $buffContainer->finishTime = Timezone::getCompleteTime($finishUnitTime);

                    $buffId = BuffServices::createBuff($buffContainer);
                    CtxException::alreadyExistsBuff($buffId === -1);
                    $buffArr = BuffServices::getBuff($buffId)->toArray();
                    $ctx->addBody(['buff' => $buffArr]);
                } else {
                    // 자원 소모 버프
                    $ctx->next();
                }
                $ctx->send();
            },
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                // 자원 소모 버프
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $buffType = $data['buff_type'];

                $plan = Plan::getData(PLAN_BUFF, $buffType);
                $planBuffType = $plan['type'];
                $finishUnitTime = $plan['default_finish_time'];
                $neededTactical = $plan['need_tactical_resource'];
                $neededFood = $plan['need_food_resource'];
                $neededLuxury = $plan['need_luxury_resource'];

                // TODO: 충성도 적용
                // 버프 만료 시간
                $finishTime = Timezone::getCompleteTime($finishUnitTime);

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);
                CtxException::invalidId($user->isEmpty());

                $resourceRatio = 1;
                if ($planBuffType === PLAN_BUFF_TYPE_RESOURCE_MANPOWER) {
                    // 자원 소모 인구 비례
                    $resourceRatio = $user->manpower;
                }

                $neededTactical *= $resourceRatio;
                $neededFood *= $resourceRatio;
                $neededLuxury *= $resourceRatio;

                // 필요한 재료를 가지고 있는 지 검사
                $hasResource = $user->hasSufficientResource($neededTactical, $neededFood, $neededLuxury);
                CtxException::resourceInsufficient(!$hasResource);

                $buffContainer = new BuffDAO();
                $buffContainer->userId = $userId;
                $buffContainer->buffType = $buffType;
                $buffContainer->finishTime = $finishTime;

                DB::beginTransaction();
                $buffId = BuffServices::createBuff($buffContainer);
                CtxException::alreadyExistsBuff($buffId === -1);
                UserServices
                    ::watchUserId($userId)
                    ::modifyUserResource(-$neededTactical, -$neededFood, -$neededLuxury)
                    ::apply();
                DB::endTransaction();

                $buffArr = BuffServices::getBuff($buffId)->toArray();
                $ctx->addBody(['buff' => $buffArr]);
                $ctx->send();
            }
        );
    }
}
