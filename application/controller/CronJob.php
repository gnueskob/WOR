<?php

namespace lsb\App\controller;

use lsb\App\models\BossDAO;
use lsb\Config\libs\Rank;
use lsb\Libs\Context;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;

class CronJob extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        /*************************************************************************************************************
         * 보스 젠
         *************************************************************************************************************/
        $router->post('/generate/boss/:type', function (Context $ctx) {
            $data = $ctx->getReqBody();

            $bossType = $data['type'];
            BossDAO::create($bossType);
        });

        /*************************************************************************************************************
         * 랭킹 업데이트
         *************************************************************************************************************/
        $router->post('/rank', function () {
            Rank::flushRank();
        });
    }
}
