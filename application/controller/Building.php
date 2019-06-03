<?php

namespace lsb\App\controller;

use lsb\App\services\BuildingServices;
use lsb\App\services\UserServices;
use lsb\Libs\CtxException;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;

class Building extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // 유저 빌딩 정보
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->req->getParams();
            $res = BuildingServices::selectUserBuilding($data);
            if ($res === false) {
                (new CtxException())->invalidId();
            }
            $ctx->res->body = $res;
            $ctx->res->send();
        });

        // 유저 빌딩 추가
        $router->post('/add/:user_id', function (Context $ctx) {
            $data = $ctx->req->getParams();
            $res = BuildingServices::insertUserBuilding($data);
            if ($res !== true) {
                (new CtxException())->insertBuildingFail();
            }
            $ctx->res->send();
        });

        // 특정 빌딩 업그레이드 요청
        $router->post('/upgrade/:building_id', function (Context $ctx) {
            $data = array_merge($ctx->req->getParams(), $ctx->req->body);
            $res = BuildingServices::deleteBuildingUpgrade($data);
            if ($res === 0) {
                (new CtxException())->deleteBuildingUpgradeFail();
            }

            // TODO: 재료 검사
            $res = UserServices::selectUserInfo($data);
            if ($res['food_resource'] >= Plan)

            $res = BuildingServices::selectBuilding($data);
            if ($res === false) {
                (new CtxException())->invalidBuildingId();
            }

            $data['from_level'] = $res['upgrade'];
            $data['to_level'] = $res['upgrade'] + 1;

            $res = BuildingServices::insertBuildingUpgrade($data);
            if ($res === false) {
                (new CtxException())->insertBuildingUpgradeFail();
            }
        });

        // 특정 빌딩 업그레이드 완료 요청
        $router->put('/upgrade/:building_id', function (Context $ctx) {
            $data = $ctx->req->getParams();

            $res = BuildingServices::updateBuildingUpgrade($data);
            if ($res === 0) {
                (new CtxException())->updateBuildingUpgradeFail();
            }

            $res = BuildingServices::deleteBuildingUpgrade($data);
            if ($res === 0) {
                (new CtxException())->deleteBuildingUpgradeFail();
            }

            $res = BuildingServices::selectBuilding($data);
            if ($res === false) {
                (new CtxException())->invalidBuildingId();
            }

            $ctx->res->body = $res;
            $ctx->res->send();
        });
    }
}
