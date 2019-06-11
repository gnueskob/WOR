<?php

namespace lsb\App\controller;

use Exception;
use lsb\App\models\Utils;
use lsb\App\services\ExploratoinServices;
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

class Exploration extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저 타일 탐사 정보
        $router->get('/tile/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $tiles = ExploratoinServices::getTilesByUser($userId);
            $ctx->addBody(['tiles' => Utils::toArrayAll($tiles)]);
            $ctx->send();
        });

        // 영내 타일 탐사 요청
        $router->post('/tile/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $tileId = $data['tile_id'];

            // 단위 수치 기획 정보
            $unit = Plan::getDataAll(PLAN_UNIT);

            // 해당 타일 정보
            $tile = Plan::getData(PLAN_TILE, $tileId);

            if ($tile['type'] === 0) {
                (new CtxException())->notUsedTile();
            }

            // 영토내 타일 가운데 좌표
            $centerX = (int) (($unit['max_tile_width']['value'] - 1) / 2);
            $centerY = (int) (($unit['max_tile_height']['value'] - 1) / 2);

            // 탐사하려는 영토의 위치
            $x = $tile['location_x'];
            $y = $tile['location_y'];

            // L2 거리
            $l2dist = abs($x - $centerX) + abs($y - $centerY);
            $takenTime = $unit['unit_time'] * $unit['tile_explore_time_coeff'] * $l2dist;

            $exploreTime = (new Timezone())->addDate("{$takenTime} seconds")->getTime();
            $exploreId = ExploratoinServices::exploreTile($userId, $tileId, $exploreTime);

            $tile = ExploratoinServices::getTile($exploreId);
            $ctx->addBody(['tile' => Utils::toArray($tile)]);
            $ctx->send();
        });

        // 영내 타일 탐사 완료 확인
        $router->get('/tile/:explore_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $exploreId = $data['explore_id'];
            $tile = ExploratoinServices::getTile($exploreId);
            if ($tile->exploreTime > Timezone::getNowUTC()) {
                (new CtxException())->notCompletedYet();
            }
            $ctx->addBody(['tile' => Utils::toArray($tile)]);
            $ctx->send();
        });

        // 외부 영토 탐사 정보
        $router->get('/territory/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $territories = ExploratoinServices::getTerritoriesByUser($userId);
            $ctx->addBody(['territories' => Utils::toArrayAll($territories)]);
            $ctx->send();
        });

        // 외부 영토 탐사 요청
        $router->post('/territory/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $territoryId = $data['territory_id'];

            // 단위 별 기획 데이터
            $unit = Plan::getDataAll(PLAN_UNIT);

            // 해당 영토 정보
            $territory = Plan::getData(PLAN_TERRITORY, $territoryId);

            if ($territory['type'] === 0) {
                (new CtxException())->notUsedTerritory();
            }

            // 탐사 지점까지의 유클리드 거리, 걸리는 시간 계산
            $centerX = (int) ($unit[TERRITORY_W]['value'] / 2);
            $centerY = (int) ($unit[TERRITORY_H]['value'] / 2);
            $x = $territory['location_x'];
            $y = $territory['location_y'];
            $l2dist = abs($x - $centerX) + abs($y - $centerY);

            $unitTime = $unit[UNIT_TIME]['value'];
            $timeCoefficient = $unit[TERRITORY_EXPLORE_UNIT_TIME]['value'];
            $takenTime = $unitTime * $timeCoefficient * $l2dist;

            // 인력 소모가 필요하므로..
            $spinlockKey = SpinLock::getKey(MANPOWER, $userId);
            SpinLock::spinLock($spinlockKey, 1);

            $user = UserServices::getUserInfo($userId);

            // 투입 인력만큼의 가용 인력을 보유 중 인지 확인
            $neededManpower = $unit[TERRITORY_EXPLORE_MANPOWER]['value'];
            if ($user->manpowerAvailable < $neededManpower) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->manpowerInsufficient();
            }

            $manpowerUsed = $user->manpowerUsed - $neededManpower;
            $exploreTime = (new Timezone())->addDate("{$takenTime} seconds")->getTime();

            $db = DB::getInstance()->getDBConnection();
            try {
                $db->beginTransaction();

                $exploreId = ExploratoinServices::exploreTerritory($userId, $territoryId, $exploreTime);

                UserServices::modifyUserUsedManpower($userId, $manpowerUsed);

                if ($db->commit() === false) {
                    (new CtxException())->transactionFail();
                }
            } catch (CtxException | PDOException | Exception $e) {
                $db->rollBack();
                SpinLock::spinUnlock($spinlockKey);
                throw $e;
            }
            SpinLock::spinUnlock($spinlockKey);

            $territory = ExploratoinServices::getTerritory($exploreId);
            $ctx->addBody(['territory' => Utils::toArray($territory)]);
            $ctx->send();
        });

        // 외부 영토 탐사 완료 요청
        $router->put('/territory/:explore_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $exploreId = $data['explore_id'];
            $territory = ExploratoinServices::getTerritory($exploreId);
            if ($territory->exploreTime > Timezone::getNowUTC()) {
                (new CtxException())->notCompletedYet();
            }
            $ctx->addBody(['tile' => Utils::toArray($territory)]);
            $ctx->send();
        });
    }
}
