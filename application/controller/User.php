<?php

namespace lsb\App\controller;

use lsb\App\models\Utils;
use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\SpinLock;
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
            if (is_null($user)) {
                (new CtxException())->invaildUser();
            }

            // 로그인 성공 시 마지막 방문일자 갱신
            UserServices::setUserLastVisit($user->userId, Timezone::getNowUTC());

            $user = UserServices::getUser($user->userId);
            Utils::checkNull($user);
            $ctx->addBody(['user' => Utils::toArray($user)]);
            $ctx->addBody(['token' => "token_temp"]);
            $ctx->send();
        });

        // hive 정보로 회원가입
        $router->post('/register', function (Context $ctx) {
            $data = $ctx->getBody();

            // 하이브 정보로 유저 검색 후 이미 존재하면 fail
            $hiveId = $data['hive_id'];
            $hiveUid = $data['hive_uid'];
            $user = UserServices::getUserByHive($hiveId, $hiveUid);
            if (isset($user)) {
                (new CtxException())->alreadyRegistered();
            }

            // 없는 정보일 시 새로운 계정 생성
            $userId = UserServices::registerNewAccount($hiveId, $hiveUid);

            $user = UserServices::getUser($userId);
            Utils::checkNull($user);
            $ctx->addBody(['user' => Utils::toArray($user)]);
            $ctx->addBody(['token' => 'token_temp']);
            $ctx->send();
        });

        // 이름 변경
        $router->put('/name/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            // 최초 로그인 후 영주 이름 설정
            $userId = $data['user_id'];
            $name = $data['name'];

            $isSuccess = UserServices::setUserName($userId, $name);
            if ($isSuccess === false) {
                (new CtxException())->alreadyUsedName();
            }
            $user = UserServices::getUser($userId);
            Utils::checkNull($user);
            $ctx->addBody(['user' => Utils::toArray($user)]);
            $ctx->send();
        });

        // 영토 변경
        $router->put('/territory/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            // 최초 로그인 시 영토 지정
            $userId = $data['user_id'];
            $territoryId = $data['territory_id'];

            $isSuccess = UserServices::setUserTerritory($userId, $territoryId);
            if ($isSuccess === false) {
                (new CtxException())->alreadyUsedTerritory();
            }

            $user = UserServices::getUser($userId);
            Utils::checkNull($user);
            $ctx->addBody(['user' => Utils::toArray($user)]);
            $ctx->send();
        });

        // 유저 정보 검색
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $user = UserServices::getUser($userId);
            Utils::checkNull($user);
            $ctx->addBody(['user' => Utils::toArray($user)]);
            $ctx->send();
        });

        // 성 업그레이드 요청
        $router->post('/upgrade/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();

            $userId = $data['user_id'];
            // 자원 확인, 소모 사이에 외부에서의 자원량 갱신이 없어야함
            $spinlockKey = SpinLock::getKey(RESOURCE, $userId);
            SpinLock::spinLock($spinlockKey, 1);

            // 유저 자원 정보 확인
            $user = UserServices::getUserInfo($userId);
            Utils::checkNull($user);

            // 이미 업그레이드 진행중 인지 검사
            if ($user->upgradeTime > Timezone::getNowUTC()) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->notCompletedYet();
            }

            $currentCastleLevel = $user->castleLevel;
            $plan = Plan::getData(PLAN_UPG_CASTLE, $currentCastleLevel);

            // 업그레이드 후 남는 자원
            $tacticalResource = $user->tacticalResource - $plan['need_tactical_resource'];
            $foodResource = $user->foodResource - $plan['need_food_resource'];
            $luxuryResource = $user->luxuryResource - $plan['need_luxury_resource'];

            // 필요한 재료를 가지고 있는 지 검사
            if ($tacticalResource < 0 || $foodResource < 0 || $luxuryResource < 0) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->resourceInsufficient();
            }

            // TODO: 완료 시간 기획 데이터로 변환
            $upgradeTime = (new Timezone())->addDate('600 seconds')->getTime();

            UserServices::upgradeUserCastle(
                $userId,
                $tacticalResource,
                $foodResource,
                $luxuryResource,
                $currentCastleLevel,
                $upgradeTime
            );
            SpinLock::spinUnlock($spinlockKey);

            $user = UserServices::getUser($userId);
            Utils::checkNull($user);
            $ctx->addBody(['user' => Utils::toArray($user)]);
            $ctx->send();
        });

        // 성 업그레이드 완료 확인
        $router->get('/upgrade/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $user = UserServices::getUser($userId);
            Utils::checkNull($user);
            if ($user->upgradeTime > Timezone::getNowUTC()) {
                (new CtxException())->notCompletedYet();
            }
            $ctx->addBody(['user' => Utils::toArray($user)]);
            $ctx->send();
        });
    }
}
