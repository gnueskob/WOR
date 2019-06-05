<?php

namespace lsb\App\controller;

use lsb\App\services\ExploratoinServices;
use lsb\App\services\UserServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;
use lsb\Libs\SpinLock;
use lsb\Libs\Timezone;

class Exploration extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저 타일 탐사 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $res = ExploratoinServices::getTilesByUser($data);
            $ctx->addBody($res);
            $ctx->send();
        });

        // 영내 타일 탐사 요청
        $router->post('/tile/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();

            // 영토당 최대 타일 수
            $unit = Plan::getDataAll(PLAN_UNIT);

            // 해당 타일 정보
            $tile = Plan::getData(PLAN_TILE, $data['tile_id']);

            if ($tile['type'] === 0) {
                (new CtxException())->notUsedTile();
            }

            $centerX = (int) ($unit['max_tile_width']['value'] / 2);
            $centerY = (int) ($unit['max_tile_height']['value'] / 2);
            $x = $tile['location_x'];
            $y = $tile['location_y'];
            $l2dist = abs($x - $centerX) + abs($y - $centerY);
            $takenTime = $unit['unit_time'] * $unit['tile_explore_time_coeff'] * $l2dist;

            $data['explore_finish_time'] = (new Timezone())->addDate("{$takenTime} seconds");
            $exploreId = ExploratoinServices::exploreTile($data);

            $data['explore_id'] = $exploreId;
            $ctx->addBody(ExploratoinServices::getTile($data));
            $ctx->send();
        });

        // 영내 타일 탐사 완료 요청
        $router->put('/tile/:explore_id', function (Context $ctx) {
            $data = $ctx->getBody();
            ExploratoinServices::resolveExploreTile($data);
            $ctx->addBody(ExploratoinServices::getTile($data));
            $ctx->send();
        });

        // 외부 영토 탐사 요청
        $router->post('/territory/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();

            // 단위 별 기획 데이터
            $unit = Plan::getDataAll(PLAN_UNIT);

            // 해당 영토 정보
            $territory = Plan::getData(PLAN_TERRITORY, $data['territory_id']);

            if ($territory['type'] === 0) {
                (new CtxException())->notUsedTerritory();
            }

            // 탐사 지점까지의 유클리드 거리, 걸리는 시간 계산
            $centerX = (int) ($unit['max_territory_width']['value'] / 2);
            $centerY = (int) ($unit['max_territory_height']['value'] / 2);
            $x = $territory['location_x'];
            $y = $territory['location_y'];
            $l2dist = abs($x - $centerX) + abs($y - $centerY);

            $unitTime = $unit['unit_time']['value'];
            $timeCoefficient = $unit['territory_explore_time_coeff']['value'];
            $takenTime = $unitTime * $timeCoefficient * $l2dist;

            // 인력 소모가 필요하므로..
            $spinlockKey = SpinLock::getKey(MANPOWER, $data['user_id']);
            SpinLock::spinLock($spinlockKey, 1);

            $user = UserServices::getUserInfo($data);

            $availableManpower = $user['manpower'] - $user['manpower_used'];
            $neededManpower = $unit['territory_explore_manpower']['value'];
            if ($neededManpower > $availableManpower) {
                SpinLock::spinUnlock($spinlockKey);
                (new CtxException())->manpowerInsufficient();
            }

            $data['manpower_used'] = $neededManpower;
            $data['explore_finish_time'] = (new Timezone())->addDate("{$takenTime} seconds");
            $exploreId = ExploratoinServices::exploreTerritory($data);
            SpinLock::spinUnlock($spinlockKey);

            $data['explore_id'] = $exploreId;
            $ctx->addBody(ExploratoinServices::getTerritory($data));
            $ctx->send();
        });

        // 외부 영토 탐사 완료 요청
        $router->put('/territory/:explore_id', function (Context $ctx) {
            $data = $ctx->getBody();
            ExploratoinServices::resolveExploreTerritory($data);
            $ctx->addBody(ExploratoinServices::getTerritory($data));
            $ctx->send();
        });
    }
}
