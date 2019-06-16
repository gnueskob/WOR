<?php

namespace lsb\App\controller;

use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\UserServices;
use lsb\Libs\Context;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;

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

            ExploratoinServices::checkTileAvailable($tileId);

            // 단위 수치 기획 정보
            list($timeCoefficient) = Plan::getUnitExplore();

            // 타겟 타일 까지의 거리
            $dist = ExploratoinServices::getDistanceToTargetTile($tileId);

            $exploreId = ExploratoinServices::exploreTile($userId, $tileId, $timeCoefficient * $dist);

            $tileArr = ExploratoinServices::getTile($exploreId)->toArray();
            $ctx->addBody(['tile' => $tileArr]);
            $ctx->send();
        });

        // 영내 타일 탐사 완료 확인
        $router->get('/tile/:explore_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $exploreId = $data['explore_id'];

            $tile = ExploratoinServices::getTile($exploreId);
            ExploratoinServices::checkTileExplored($tile);

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
                list(, $timeCoefficient, $exploreManpower) = Plan::getUnitExplore();

                ExploratoinServices::checkTerritoryAvailable($territoryId);

                $user = UserServices::getUserInfo($userId);

                // 투입 인력만큼의 가용 인력을 보유 중 인지 확인
                UserServices::checkAvailableManpowerSufficient($user, $exploreManpower);

                // 탐사 지점까지의 유클리드 거리, 걸리는 시간 계산
                $dist = ExploratoinServices::getDistanceToTargetTerritory($user->territoryId, $territoryId);

                $exploreId = ExploratoinServices::exploreTerritory($userId, $territoryId, $timeCoefficient * $dist);

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
            ExploratoinServices::checkTerritoryExplored($territory);

            $ctx->addBody(['territory' => $territory->toArray()]);
            $ctx->send();
        });
    }
}
