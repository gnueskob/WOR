<?php

namespace lsb\App\controller;

use Exception;
use lsb\App\models\Utils;
use lsb\App\services\BufServices;
use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\Plan;
use lsb\Libs\SpinLock;
use lsb\Libs\Timezone;
use PDOException;

class Buf extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저에게 적용중인 버프 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];

            // 버프 정보 불러오기 전 만료된 버프 삭제
            BufServices::refreshBuf($userId);

            $bufs = BufServices::getBufsByUser($userId);
            $ctx->addBody(['bufs' => Utils::toArrayAll($bufs)]);
            $ctx->send();
        });

        // 버프 추가 (충성도 버프)
        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $bufType = $data['buf_type'];

            // 버프 추가 전 만료 버프 삭제
            BufServices::refreshBuf($userId);

            $plan = Plan::getData(PLAN_BUF, $bufType);

            // TODO: 충성도 적용
            $defaultFinishTime = $plan['default_finish_time'];
            $finishTime = (new Timezone())->addDate("{$defaultFinishTime} seconds");

            if ($plan['type'] === 0) {
                // 전리품 버프
                $bufId = BufServices::makeBuf($userId, $bufType, $finishTime);
            } else {
                // 자원 소모 버프

                // 자원을 확인하고 소모시키는 중간 부분에서 자원량이 갱신되면 안됨
                $spinlockKey = SpinLock::getKey(RESOURCE, $userId);
                SpinLock::spinLock($spinlockKey, 1);

                // 현재 유저 자원 정보
                $user = UserServices::getUserInfo($userId);
                Utils::checkNull($user);

                $resourceRatio = 1;
                if ($plan['type'] === 2) {
                    // 자원 소모 인구 비례
                    $resourceRatio = $user->manpower;
                }

                $needTacticalResource = $resourceRatio * $plan['need_tactical_resource'];
                $needFoodResource = $resourceRatio * $plan['need_food_resource'];
                $needLuxuryResource = $resourceRatio * $plan['need_luxury_resource'];

                $tacticalResource = $user->tacticalResource - $needTacticalResource;
                $foodResource = $user->foodResource - $needFoodResource;
                $luxuryResource = $user->luxuryResource - $needLuxuryResource;

                if ($tacticalResource < 0 || $foodResource < 0 || $luxuryResource < 0) {
                    SpinLock::spinUnlock($spinlockKey);
                    (new CtxException())->resourceInsufficient();
                }

                $db = DB::getInstance()->getDBConnection();
                try {
                    $db->beginTransaction();

                    UserServices::modifyUserResource(
                        $userId,
                        $tacticalResource,
                        $foodResource,
                        $luxuryResource
                    );

                    $bufId = BufServices::makeBuf($userId, $bufType, $finishTime);

                    if ($db->commit() === false) {
                        (new CtxException())->transactionFail();
                    }
                } catch (CtxException | PDOException | Exception $e) {
                    $db->rollBack();
                    SpinLock::spinUnlock($spinlockKey);
                    throw $e;
                }
                SpinLock::spinUnlock($spinlockKey);
            }

            $buf = BufServices::getBuf($bufId);
            Utils::checkNull($buf);
            $ctx->addBody(['buf' => Utils::toArray($buf)]);
            $ctx->send();
        });
    }
}
