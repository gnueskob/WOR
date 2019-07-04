<?php

namespace lsb\App\controller;

use lsb\App\models\BuffDAO;
use lsb\App\models\UserDAO;
use lsb\Libs\CtxException as CE;
use lsb\Libs\ErrorCode;
use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\Plan;

class Buff extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        /*************************************************************************************************************
         * 유저에게 적용중인 버프 정보
         *************************************************************************************************************/
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];

            // 버프 정보 불러오기 전 만료된 버프 삭제
            BuffDAO::container()->resolveExpiredBuff($userId);

            $buffs = BuffDAO::getBuffs($userId);
            $ctx->addResBody(['buffs' => Utils::toArrayAll($buffs)]);
        });

        /*************************************************************************************************************
         * 버프 추가 (충성도 버프)
         *************************************************************************************************************/
        $router->post(
            '/add',
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $userId = $data['user_id'];
                $buffType = $data['buff_type'];

                // 버프 추가 전 만료 버프 삭제
                BuffDAO::container()->resolveExpiredBuff($userId);

                list($buffClass) = Plan::getBuffClass($buffType);
                $finishUnitTime = Plan::getBuffFinishUnitTime($buffType);

                if ($buffClass === Plan::BUFF_TYPE_TROPHY) {
                    // 전리품 버프
                    $buffId = BuffDAO::createBuff($userId, $buffType, $finishUnitTime);

                    $buff = BuffDAO::getBuff($buffId);
                    $ctx->addResBody(['buff' => $buff->toArray()]);
                    return;
                }

                // 자원 소모 버프
                $ctx->next();
            },
            Lock::lock(RESOURCE),
            function (Context $ctx) {
                // 자원 소모 버프
                $data = $ctx->getReqBody();
                $userId = $data['user_id'];
                $buffType = $data['buff_type'];

                list($buffClass) = Plan::getBuffClass($buffType);
                $finishUnitTime = Plan::getBuffFinishUnitTime($buffType);
                list($tatical, $food, $luxury) = Plan::getBuffResources($buffType);

                // 현재 유저 자원 정보
                $user = UserDAO::getUserInfo($userId);

                if ($buffClass === Plan::BUFF_TYPE_RESOURCE_MANPOWER) {
                    // 자원 소모 인구 비례
                    $tatical *= $user->manpower;
                    $food *= $user->manpower;
                    $luxury *= $user->manpower;
                }

                // 필요한 재료를 가지고 있는 지 검사
                CE::check(false === $user->hasResource($tatical, $food, $luxury), ErrorCode::RESOURCE_INSUFFICIENT);

                DB::beginTransaction();
                $user->useResources($tatical, $food, $luxury);
                $buffId = BuffDAO::createBuff($userId, $buffType, $finishUnitTime);
                DB::endTransaction();

                $buff = BuffDAO::getBuff($buffId);
                $ctx->addResBody(['buff' => $buff->toArray()]);
            }
        );
    }
}
