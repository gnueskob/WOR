<?php

namespace lsb\App\controller;

use lsb\App\services\BuildingServices;
use lsb\Libs\DB;
use lsb\Libs\SpinLock;
use lsb\Utils\Lock;
use lsb\App\services\UserServices;
use lsb\Libs\ISubRouter;
use lsb\Libs\Router;
use lsb\Libs\Context;
use lsb\Libs\Plan;

class User extends Router implements ISubRouter
{
    public function make()
    {
        $router = $this;

        // hive 정보로 로그인
        $router->put('/login', function (Context $ctx) {
            $data = $ctx->getBody();
            $hiveId = $data['hive_id'];
            $hiveUid = $data['hive_uid'];

            // 하이브 정보로 유저 검색 후 없는 경우 비정상 유저
            $user = UserServices::checkHiveUserExists($hiveId, $hiveUid);
            $userId = $user->userId;

            UserServices::visit($userId);

            // TODO: token 생성
            $userArr = UserServices::getUser($userId)->toArray();
            $ctx->addBody([
                'user' => $userArr,
                'token' => 'token_temp'
            ]);
            $ctx->send();
        });

        // hive 정보로 회원가입
        $router->post('/register', function (Context $ctx) {
            $data = $ctx->getBody();
            $hiveId = $data['hive_id'];
            $hiveUid = $data['hive_uid'];
            $country = $data['country'];
            $lang = $data['lang'];
            $osVersion = $data['os_version'];
            $appVersion = $data['app_version'];

            // 하이브 정보로 유저 검색 후 이미 존재하면 fail
            UserServices::checkNewHiveUser($hiveId, $hiveUid);

            // 없는 정보일 시 새로운 계정 생성
            DB::beginTransaction();
            $userId = UserServices::createNewUserPlatform(
                $hiveId,
                $hiveUid,
                $country,
                $lang,
                $osVersion,
                $appVersion
            );
            UserServices::createNewUserInfo($userId);
            UserServices::createNewUserStat($userId);
            DB::endTransaction();

            $userArr = UserServices::getUser($userId)->toArray();
            // TODO: token 생성
            $ctx->addBody([
                'user' => $userArr,
                'token' => 'token_temp'
            ]);
            $ctx->send();
        });

        // 이름 변경
        $router->put('/name/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $name = $data['name'];

            // 최초 로그인 후 영주 이름 설정
            UserServices::rename($userId, $name);

            $userArr = UserServices::getUser($userId)->toArray();
            $ctx->addBody(['user' => $userArr]);
            $ctx->send();
        });

        // 영토 변경
        $router->put('/territory/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $territoryId = $data['territory_id'];

            // 최초 로그인 시 영토 지정
            UserServices::relocateTerritory($userId, $territoryId);

            $userArr = UserServices::getUser($userId)->toArray();
            $ctx->addBody(['user' => $userArr]);
            $ctx->send();
        });

        // 유저 정보 검색
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $userArr = UserServices::getUser($userId)->toArray();
            $ctx->addBody(['user' => $userArr]);
            $ctx->send();
        });

        // 성 업그레이드 요청
        $router->post(
            '/upgrade/:user_id',
            // 여러 단말기로 API 여러번 날리는 경우 방지
            // 자원 확인, 소모 사이에 외부에서의 자원량 갱신이 없어야함
            Lock::lockUser(SpinLock::RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];

                $user = UserServices::getUserInfo($userId);

                // 업그레이드에 필요한 자원
                $level = $user->currentCastleLevel;
                list($neededTactical,
                    $neededFood,
                    $neededLuxury) = Plan::getBuildingUpgradeResources(PLAN_BUILDING_ID_CASTLE, $level);
                list(, $upgradeUnitTime) = Plan::getBuildingUnitTime(PLAN_BUILDING_ID_CASTLE, $level);
                list(, $maxLevel) = Plan::getBuildingUpgradeStatus(PLAN_BUILDING_ID_CASTLE);

                // 성 업그레이드 가능 여부 검사
                UserServices::checkMaxLevelOver($user, $maxLevel);
                UserServices::checkUpgradeStatus($user);
                UserServices::checkResourceSufficient($user, $neededTactical, $neededFood, $neededLuxury);

                // 유저 자원 소모, 성 업그레이드
                UserServices::useResource($user->userId, $neededTactical, $neededFood, $neededLuxury, true);
                UserServices::upgradeCastle($user->userId, $user->currentCastleLevel, $upgradeUnitTime);

                $userArr = UserServices::getUser($userId)->toArray();
                $ctx->addBody(['user' => $userArr]);
                $ctx->send();
            }
        );

        // 성 업그레이드 완료 확인
        $router->get('/upgrade/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];
            $user = UserServices::getUser($userId);

            // 유저 성 업그레이드 완료 여부 검사
            UserServices::checkUpgradeFinished($user);

            $ctx->addBody(['user' => $user->toArray()]);
            $ctx->send();
        });

        // 동맹용 덱 등록
        $router->put('/deck/:user_id', function (Context $ctx) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];

            UserServices::registerFriendAttackPower($userId);
            $user = UserServices::getUser($userId);

            $ctx->addBody(['user' => $user->toArray()]);
            $ctx->send();
        });

        // 정산
        $router->put(
            '/calculation/:user_id',
            Lock::lockUser(SpinLock::RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getBody();
                $userId = $data['user_id'];

                $user = UserServices::getUserInfo($userId);

                // 자원 획득
                list($tactical, $food, $luxury) = BuildingServices::generateResources($userId, $user->lastVisit);
                UserServices::obtainResource($userId, $tactical, $food, $luxury);

                $user = UserServices::getUser($userId);
                $ctx->addBody(['user' => $user->toArray()]);
                $ctx->send();
            }
        );
    }
}
