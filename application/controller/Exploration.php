<?php

namespace lsb\App\controller;

use lsb\App\models\TerritoryDAO;
use lsb\App\models\TileDAO;
use lsb\App\models\UserDAO;
use lsb\Libs\CtxException as CE;
use lsb\Libs\ErrorCode;
use lsb\Utils\Lock;
use lsb\Utils\Utils;
use lsb\App\services\ExploratoinServices;
use lsb\Libs\Context;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;

class Exploration extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        /*************************************************************************************************************
         * 유저 영내 타일 탐사 정보
         *************************************************************************************************************/
        $router->get('/tile/info/:user_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];

            $tiles = TileDAO::getTiles($userId);
            $ctx->addResBody(['tiles' => Utils::toArrayAll($tiles)]);
        });

        /*************************************************************************************************************
         * 영내 타일 탐사 요청
         *************************************************************************************************************/
        $router->post('/tile', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];
            $tileId = $data['tile_id'];

            list($tileClass) = Plan::getTileClass($tileId);
            CE::check($tileClass === Plan::TILE_TYPE_NOT_USED, ErrorCode::NOT_USED_LOCATION);

            // 단위 수치 기획 정보
            list($timeCoefficient) = Plan::getUnitExplore();

            // 타겟 타일 까지의 거리
            $dist = ExploratoinServices::getDistanceToTargetTile($tileId);
            $exploreUnitTime = $timeCoefficient * $dist;

            $exploreId = TileDAO::exploreTile($userId, $tileId, $exploreUnitTime);

            $tile = TileDAO::getTile($exploreId);
            $ctx->addResBody(['tile' => $tile->toArray()]);
        });

        /*************************************************************************************************************
         * 영내 타일 탐사 확인
         *************************************************************************************************************/
        $router->get('/tile', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $exploreId = $data['explore_id'];

            $tile = TileDAO::getTile($exploreId);
            CE::check(!$tile->isExplored(), ErrorCode::IS_NOT_EXPLORED);

            $ctx->addResBody(['tile' => $tile->toArray()]);
        });

        /*************************************************************************************************************
         * 외부 영토 탐사 정보
         *************************************************************************************************************/
        $router->get('/territory/info/:user_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];

            $territories = TerritoryDAO::getTerritories($userId);
            $ctx->addResBody(['territories' => Utils::toArrayAll($territories)]);
        });

        /*************************************************************************************************************
         * 외부 영토 탐사 요청
         *************************************************************************************************************/
        $router->post(
            '/territory',
            Lock::lockUser(MANPOWER),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $userId = $data['user_id'];
                $territoryId = $data['territory_id'];

                $user = UserDAO::getUserInfo($userId);

                // 단위 별 기획 데이터
                list(, $timeCoefficient, $exploreManpower) = Plan::getUnitExplore();
                list($territoryClass) = Plan::getTerritoryClass($territoryId);

                CE::check($territoryClass === Plan::TERRITORY_TYPE_NOT_USED, ErrorCode::NOT_USED_LOCATION);
                CE::check($user->hasAvailableManpower($exploreManpower), ErrorCode::MANPOWER_INSUFFICIENT);

                // 탐사 지점까지의 유클리드 거리, 걸리는 시간 계산
                $dist = ExploratoinServices::getDistanceToTargetTerritory($user->territoryId, $territoryId);
                $exploreUnitTime = $timeCoefficient * $dist;

                $exploreId = TerritoryDAO::exploreTerritory($user->userId, $territoryId, $exploreUnitTime);

                $territory = TerritoryDAO::getTerritory($exploreId);
                $ctx->addResBody(['territory' => $territory->toArray()]);
            }
        );

        /*************************************************************************************************************
         * 외부 영토 탐사 완료
         *************************************************************************************************************/
        $router->put('/territory/:explore_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $exploreId = $data['explore_id'];

            $territory = TerritoryDAO::getTerritory($exploreId);
            CE::check(!$territory->isExplored(), ErrorCode::IS_NOT_EXPLORED);

            $ctx->addResBody(['territory' => $territory->toArray()]);
        });
    }
}
