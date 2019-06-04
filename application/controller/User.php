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

        $router->put('/login', function (Context $ctx) {
            $data = $ctx->getBody();

            $userId = UserServices::getHiveUserId($data);
            if ($userId === -1) {
                (new CtxException())->invaildUser();
            }

            $data = array_merge($data, ['last_visit' => Timezone::getNowUTC()]);
            UserServices::setUserLastVisit($data);

            $ctx->res->body = [
                'user_id' => $userId,
                'token' => 'tokentokentoken'
            ];
            $ctx->res->send();
        });

        $router->post('/register', function (Context $ctx) {
            $data = $ctx->getBody();

            $userId = UserServices::getHiveUserId($data);
            if ($userId !== false) {
                (new CtxException())->alreadyRegistered();
            }

            $userId = UserServices::registerNewAccount($data);

            $ctx->res->body = [
                'user_id' => $userId,
                'token' => 'tokentokentoken'
            ];
            $ctx->res->send();
        });

        $router->put('/name/:user_id', function (Context $ctx) {
            $data = array_merge($ctx->req->getParams(), $ctx->req->body);
            if (UserServices::setUserName($data) === false) {
                (new CtxException())->alreadyUsedName();
            }
            $ctx->res->send();
        });


        $router->put('/territory/:user_id', function (Context $ctx) {
            $data = array_merge($ctx->req->getParams(), $ctx->req->body);
            if (UserServices::setUserTerritory($data) === false) {
                (new CtxException())->alreadyUsedTerritory();
            }
            $ctx->res->send();
        });

        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $res = UserServices::getUser($data);
            if ($res === false) {
                (new CtxException())->invaildUser();
            }
            $ctx->res->body = $res;
            $ctx->res->send();
        });

        $router->post('/upgrade/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();

            $spinlockKey = "resource::{$data['user_id']}";
            SpinLock::spinLock($spinlockKey, 1);

            $res = UserServices::getUserInfo($data);

            $keyTag = PLAN_UPG_CASTLE;
            $plan = Plan::getData($keyTag, $res['upgrade']);

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
            $data['finish_time'] = (new Timezone())->addDate('600 seconds');
            UserServices::upgradeUserCastle($data);
            SpinLock::spinUnlock($spinlockKey);

            $ctx->res->body = UserServices::getUserInfo($data);
            $ctx->res->send();
        });

        $router->put('/upgrade/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $data['finish_time'] = Timezone::getNowUTC();
            UserServices::resolveUpgradeUserCastle($data);
            $ctx->res->body = UserServices::getUserInfo($data);
            $ctx->res->send();
        });
    }
}
