<?php

namespace lsb\App\controller;

use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\UserServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;
use lsb\Libs\Timezone;

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

            // 해당 타일 정보
            $plan = Plan::getData(PLAN_TILE, $tileId);
            $tileType = $plan['type'];
            $tileX = $plan['location_x'];
            $tileY = $plan['location_y'];

            CtxException::notUsedTile($tileType === PLAN_TILE_TYPE_NOT_USED);

            // 단위 수치 기획 정보
            $unit = Plan::getDataAll(PLAN_UNIT);
            $timeCoefficient = $unit[TILE_EXPLORE_UNIT_TIME]['value'];
            $tileWidth = $unit[TILE_W]['value'];
            $tileHeight = $unit[TILE_H]['value'];

            // 타겟 타일 까지의 거리
            $centerX = (int) (($tileWidth - 1) / 2);
            $centerY = (int) (($tileHeight - 1) / 2);
            $dist = Utils::getManhattanDistance($tileX, $tileY, $centerX, $centerY);

            $totalExploreUnitTime = $timeCoefficient * $dist;
            $exploreTime = Timezone::getCompleteTime($totalExploreUnitTime);

            $exploreId = ExploratoinServices::exploreTile($userId, $tileId, $exploreTime);

            $tileArr = ExploratoinServices::getTile($exploreId)->toArray();
            $ctx->addBody(['tile' => $tileArr]);
            $ctx->send();
        });

        // 영내 타일 탐사 완료 확인
        $router->get('/tile/:explore_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $exploreId = $data['explore_id'];
            $tile = ExploratoinServices::getTile($exploreId);

            CtxException::invalidId($tile->isEmpty());
            CtxException::notExploredYet(!$tile->isExplored());

            $ctx->addBody(['tile' => $tile->toArray()]);
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
        $router->post(
            '/territory/:user_id',
            Lock::lockUser(MANPOWER),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];
                $territoryId = $data['territory_id'];

                // 단위 별 기획 데이터
                $unit = Plan::getDataAll(PLAN_UNIT);
                $timeCoefficient = $unit[TERRITORY_EXPLORE_UNIT_TIME]['value'];
                $neededManpower = $unit[TERRITORY_EXPLORE_MANPOWER]['value'];

                // 해당 영토 정보
                $targetTerritory = Plan::getData(PLAN_TERRITORY, $territoryId);
                $territoryType = $targetTerritory['type'];
                $targetX = $targetTerritory['location_x'];
                $targetY = $targetTerritory['location_y'];

                CtxException::notUsedTerritory($territoryType === PLAN_TERRITORY_TYPE_NOT_USED);

                $user = UserServices::getUserInfo($userId);
                CtxException::invalidId($user->isEmpty());

                // 투입 인력만큼의 가용 인력을 보유 중 인지 확인
                CtxException::manpowerInsufficient($user->manpowerAvailable < $neededManpower);

                list($userX, $userY) = UserServices::getLocation($user->territoryId);

                // 탐사 지점까지의 유클리드 거리, 걸리는 시간 계산
                $dist = Utils::getManhattanDistance($userX, $userY, $targetX, $targetY);

                $totalExploreTime = $timeCoefficient * $dist;
                $exploreTime = Timezone::getCompleteTime($totalExploreTime);

                DB::beginTransaction();
                $exploreId = ExploratoinServices::exploreTerritory($userId, $territoryId, $exploreTime);
                UserServices
                    ::watchUserId($userId)
                    ::modifyUserManpower(0, +$neededManpower, 0)
                    ::apply();
                DB::endTransaction();

                $territoryArr = ExploratoinServices::getTerritory($exploreId)->toArray();
                $ctx->addBody(['territory' => $territoryArr]);
                $ctx->send();
            }
        );

        // 외부 영토 탐사 완료 요청
        $router->put('/territory/:explore_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $exploreId = $data['explore_id'];
            $territory = ExploratoinServices::getTerritory($exploreId);

            CtxException::invalidId($territory->isEmpty());
            CtxException::notExploredYet(!$territory->isExplored());

            $ctx->addBody(['tile' => $territory->toArray()]);
            $ctx->send();
        });
    }
}
