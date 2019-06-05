<?php

namespace lsb\App\controller;

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
            $data['user_id'] = UserServices::getUserByHive($data);
            if ($data['user_id'] === -1) {
                (new CtxException())->invaildUser();
            }

            // 로그인 성공 시 마지막 방문일자 갱신
            $data = array_merge($data, ['last_visit' => Timezone::getNowUTC()]);
            UserServices::setUserLastVisit($data);

            $ctx->addBody(UserServices::getUser($data));
            $ctx->addBody(['token' => "token_temp"]);
            $ctx->send();
        });

        // hive 정보로 회원가입
        $router->post('/register', function (Context $ctx) {
            $data = $ctx->getBody();

            // 하이브 정보로 유저 검색 후 이미 존재하면 fail
            $userId = UserServices::getUserByHive($data);
            if ($userId !== false) {
                (new CtxException())->alreadyRegistered();
            }

            // 없는 정보일 시 새로운 계정 생성
            $data['user_id'] = UserServices::registerNewAccount($data);

            $ctx->addBody(UserServices::getUser($data));
            $ctx->addBody(['token' => 'token_temp']);
            $ctx->send();
        });

        // 이름 변경
        $router->put('/name/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            // 최초 로그인 후 영주 이름 설정
            if (UserServices::setUserName($data) === false) {
                (new CtxException())->alreadyUsedName();
            }
            $ctx->addBody(UserServices::getUser($data));
            $ctx->send();
        });

        // 영토 변경
        $router->put('/territory/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            // 최초 로그인 시 영토 지정
            if (UserServices::setUserTerritory($data) === false) {
                (new CtxException())->alreadyUsedTerritory();
            }
            $ctx->addBody(UserServices::getUser($data));
            $ctx->send();
        });

        // 유저 정보 검색
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $ctx->addBody(UserServices::getUser($data));
            $ctx->send();
        });

        // 성 업그레이드 요청
        $router->post('/upgrade/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();

            // 자원 확인, 소모 사이에 외부에서의 자원량 갱신이 없어야함
            $spinlockKey = SpinLock::getKey(RESOURCE, $data['user_id']);
            SpinLock::spinLock($spinlockKey, 1);

            // 유저 자원 정보 확인
            $res = UserServices::getUserInfo($data);

            $plan = Plan::getData(PLAN_UPG_CASTLE, $res['upgrade']);

            // 필요한 재료를 가지고 있는 지 검사
            if ($plan['need_tactical_resource'] > $res['tactical_resource'] ||
                $plan['need_food_resource'] > $res['food_resource'] ||
                $plan['need_luxury_resource'] > $res['luxury_resource']) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->resourceInsufficient();
            }

            $data['need_tactical_resource'] = (-1) * $plan['need_tactical_resource'];
            $data['need_food_resource'] = (-1) * $plan['need_food_resource'];
            $data['need_luxury_resource'] = (-1) * $plan['need_luxury_resource'];
            $data['from_level'] = $res['upgrade'];
            $data['to_level'] = $res['upgrade'] + 1;
            // TODO: 완료 시간 기획 데이터로 변환
            $data['upgrade_finish_time'] = (new Timezone())->addDate('600 seconds');
            UserServices::upgradeUserCastle($data);
            SpinLock::spinUnlock($spinlockKey);

            $ctx->addBody(UserServices::getUser($data));
            $ctx->send();
        });

        // 성 업그레이드 완료 요청
        $router->put('/upgrade/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $data['finish_time'] = Timezone::getNowUTC();
            UserServices::resolveUpgradeUserCastle($data);
            $ctx->addBody(UserServices::getUser($data));
            $ctx->send();
        });
    }
}
