<?php

namespace lsb\App\controller;

use lsb\Libs\DB;
use lsb\Utils\Lock;
use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\Timezone;
use lsb\Libs\Plan;

class User extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // hive 정보로 로그인
        $router->put('/login', function (Context $ctx) {
            $data = $ctx->getBody();

            // 하이브 정보로 유저 검색 후 없는 경우 비정상 유저
            $hiveId = $data['hive_id'];
            $hiveUid = $data['hive_uid'];
            $user = UserServices::getUserByHive($hiveId, $hiveUid);
            CtxException::invalidUser($user->isEmpty());

            // 로그인 성공 시 마지막 방문일자 갱신
            $now = Timezone::getNowUTC();
            UserServices::setUserLastVisit($user->userId, $now);

            // TODO: token 생성
            $userArr = UserServices::getUser($user->userId)->toArray();
            $ctx->addBody([
                'user' => $userArr,
                'token' => 'token_temp'
            ]);
            $ctx->send();
        });

        // hive 정보로 회원가입
        $router->post('/register', function (Context $ctx) {
            $data = $ctx->getBody();

            // 하이브 정보로 유저 검색 후 이미 존재하면 fail
            $hiveId = $data['hive_id'];
            $hiveUid = $data['hive_uid'];
            $user = UserServices::getUserByHive($hiveId, $hiveUid);
            CtxException::alreadyRegistered(!$user->isEmpty());

            // 없는 정보일 시 새로운 계정 생성
            $userId = UserServices::registerNewAccount($hiveUid, $hiveId);

            // TODO: token 생성
            $userArr = UserServices::getUser($userId)->toArray();
            $ctx->addBody([
                'user' => $userArr,
                'token' => 'token_temp'
            ]);
            $ctx->send();
        });

        // 이름 변경
        $router->put('/name/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            // 최초 로그인 후 영주 이름 설정
            $userId = $data['user_id'];
            $name = $data['name'];

            $isSuccess = UserServices
                ::watchUserId($userId)
                ::setUserName($name)
                ::apply(true);
            CtxException::alreadyUsedName(!$isSuccess);

            $userArr = UserServices::getUser($userId)->toArray();
            $ctx->addBody(['user' => $userArr]);
            $ctx->send();
        });

        // 영토 변경
        $router->put('/territory/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            // 최초 로그인 시 영토 지정
            $userId = $data['user_id'];
            $territoryId = $data['territory_id'];

            $isSuccess = UserServices
                ::watchUserId($userId)
                ::setUserTerritory($territoryId)
                ::apply(true);
            CtxException::alreadyUsedTerritory(!$isSuccess);

            $userArr = UserServices::getUser($userId)->toArray();
            $ctx->addBody(['user' => $userArr]);
            $ctx->send();
        });

        // 유저 정보 검색
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $userArr = UserServices::getUser($userId)->toArray();
            $ctx->addBody(['user' => $userArr]);
            $ctx->send();
        });

        // 성 업그레이드 요청
        $router->post(
            '/upgrade/:user_id',
            // 여러 단말기로 API 여러번 날리는 경우 방지
            // 자원 확인, 소모 사이에 외부에서의 자원량 갱신이 없어야함
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];

                // 유저 자원 정보 확인
                $user = UserServices::getUserInfo($userId);
                CtxException::invalidId($user->isEmpty());

                // 이미 업그레이드 진행중 인지 검사
                CtxException::notUpgradedYet($user->isUpgrading());

                // 업그레이드에 필요한 자원
                $plan = Plan::getData(PLAN_UPG_CASTLE, $user->currentCastleLevel);
                $neededTactical = $plan['need_tactical_resource'];
                $neededFood = $plan['need_food_resource'];
                $neededLuxury = $plan['need_luxury_resource'];

                // 필요한 재료를 가지고 있는 지 검사
                $hasResource = $user->hasSufficientResource($neededTactical, $neededFood, $neededLuxury);
                CtxException::resourceInsufficient(!$hasResource);

                // 업그레이드에 필요한 시간
                $castleUpgradeUnitTime = Plan::getData(PLAN_BUILDING, PLAN_BUILDING_ID_CASTLE)['upgrade_unit_time'];
                $upgradeTime = Timezone::getCompleteTime($castleUpgradeUnitTime);

                // 유저 자원 소모, 성 업그레이드
                UserServices
                    ::watchUserId($userId)
                    ::modifyUserResource(-$neededTactical, -$neededFood, -$neededLuxury)
                    ::upgradeUserCastle($user->currentCastleLevel, $upgradeTime)
                    ::apply();

                $userArr = UserServices::getUser($userId)->toArray();
                $ctx->addBody(['user' => $userArr]);
                $ctx->send();
            }
        );

        // 성 업그레이드 완료 확인
        $router->get('/upgrade/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $user = UserServices::getUser($userId);

            CtxException::invalidId($user->isEmpty());
            CtxException::notUpgradedYet(!$user->isUpgraded());

            $ctx->addBody(['user' => $user->toArray()]);
            $ctx->send();
        });
    }
}
