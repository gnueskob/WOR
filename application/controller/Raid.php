<?php

namespace lsb\App\controller;

use lsb\App\models\AllianceDAO;
use lsb\App\models\BossDAO;
use lsb\App\models\RaidDAO;
use lsb\App\models\UserDAO;
use lsb\App\services\AllianceServices;
use lsb\App\services\BuildingServices;
use lsb\App\services\ExploratoinServices;
use lsb\App\services\RaidServices;
use lsb\App\services\UserServices;
use lsb\App\services\WeaponServices;
use lsb\Libs\Context;
use lsb\Libs\CtxException as CE;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\ISubRouter;
use lsb\Libs\Plan;
use lsb\Libs\Router;
use lsb\Libs\SpinLock;
use lsb\Libs\Timezone;
use lsb\Utils\Lock;

class Raid extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        /*************************************************************************************************************
         * 유저 레이드 정보
         *************************************************************************************************************/
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->req->getParams();
            $userId = $data['user_id'];

            $raid = RaidDAO::getRaidInUser($userId);
            $ctx->addResBody(['raid' => $raid->toArray()]);
        });

        /*************************************************************************************************************
         * 완료된 레이드 조회
         *************************************************************************************************************/
        $router->get('/check', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $userId = $data['user_id'];

            $raid = RaidDAO::getFinishedRaid($userId);
            $ctx->addResBody(['raid' => $raid->toArray()]);
            $ctx->send();
        });

        /*************************************************************************************************************
         * 레이드 출전
         *************************************************************************************************************/
        $router->post(
            '/add',
            Lock::lockUser(MANPOWER, 3),
            Lock::lockUser(RESOURCE, 2),
            function (Context $ctx) {
                $data = $ctx->getReqBody();
                $userId = $data['user_id'];
                $targetTerritoryId = $data['territory_id'];

                // 이미 출전 중 인가?
                $raid = RaidDAO::getRaids($userId);
                CE::check(false === $raid->isEmpty() && false === $raid->isFinished(), ErrorCode::ALREADY_RAID);

                list($territoryClass) = Plan::getTerritoryClass($targetTerritoryId);
                CE::check($territoryClass !== Plan::TERRITORY_TYPE_BOSS, ErrorCode::IS_NOT_BOSS_TYPE);

                // 유저가 먼저 해당 영토를 탐사 했는가?
                ExploratoinServices::checkUserExploredTerritory($userId, $targetTerritoryId);

                // 해당 위치에 보스가 존재 하는가?
                $boss = BossDAO::getBossInTerritory($targetTerritoryId);
                CE::check($boss->isEmpty(), ErrorCode::BOSS_NOT_GEN);

                // 이미 전투가 시작된 보스라면 공격 가능한 보스인가?
                if (isset($boss->userId)) {
                    AllianceServices::checkAllianceWithFriend($userId, $boss->userId);
                }

                $user = UserDAO::getUserInfo($userId);

                // 타겟 영토까지의 거리
                $dist = ExploratoinServices::getDistanceToTargetTerritory($user->territoryId, $targetTerritoryId);

                // 단위 별 기획 데이터
                list($prepareUnitTime, $moveUnitTimeCoeff, $resourceCoeff) = Plan::getUnitWar();

                // 출전 준비 시간 + 이동 시간
                $finishUnitTime = $moveUnitTimeCoeff * $dist + $prepareUnitTime;

                $finishTime = Timezone::getCompleteTime($finishUnitTime);
                CE::check(isset($boss->finishTime) && $finishTime > $boss->finishTime, ErrorCode::TOO_LATE);

                // 병영에 등록된 총 병력, 공격력
                list($armyManpower, $armyAttack) = BuildingServices::getArmyManpowerAndAttack($userId);

                // 유저가 가지고 있는 무기 별 총 공격력
                $weaponAttack = WeaponServices::getAttackPower($userId);
                $totalAttackPower = $armyAttack + $weaponAttack;

                // 총 필요한 군량
                $food = $resourceCoeff * $armyManpower * $dist;
                CE::check(false === $user->hasResource(0, $food, 0), ErrorCode::RESOURCE_INSUFFICIENT);

                SpinLock::spinLock(BOSS, 1);

                DB::beginTransaction();
                UserServices::useManpower($userId, $armyManpower, true);
                UserServices::useResource($userId, 0, $neededFoodResource, 0);
                $raidId = RaidServices::createRaid($boss, $userId, $targetTerritoryId, $finishUnitTime);
                RaidServices::attackBoss($boss, $userId, $totalAttackPower);
                DB::endTransaction();

                SpinLock::spinUnlock(BOSS);

                $warArr = RaidServices::getRaid($raidId)->toArray();
                $ctx->addBody(['war' => $warArr]);
            }
        );

        // 레이드 완료 확인
        $router->put(
            '/add/:raid_id',
            Lock::lockUser(MANPOWER, 2),
            Lock::lockUser(RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $raidId = $data['raid_id'];

                $raid = RaidServices::getRaid($raidId);
                RaidServices::checkFinished($raid);

                DB::beginTransaction();
                RaidServices::resolveRaidResult($raid);
                RaidServices::removeRaid($raid->raidId);
                DB::endTransaction();

                $userArr = UserServices::getUser($raid->userId)->toArray();
                $ctx->addBody(['user' => $userArr]);
                $ctx->send();
            }
        );
    }
}
