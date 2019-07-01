<?php

namespace lsb\App\controller;

use lsb\App\models\UserDAO;
use lsb\App\services\BuildingServices;
use lsb\Libs\CtxException as CE;
use lsb\Libs\DB;
use lsb\Libs\ErrorCode;
use lsb\Libs\SpinLock;
use lsb\Utils\Auth;
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

        /*************************************************************************************************************
         * hive 정보로 로그인
         *************************************************************************************************************/
        $router->put('/login', function (Context $ctx) {
            $data = $ctx->getReqBody();

            // 하이브 정보에 해당하는 유저 검색
            $user = UserDAO::getHiveUser($data['hive_id'], $data['hive_uid']);
            CE::check($user->isEmpty(), ErrorCode::INVALID_USER);

            // 방문 시각 갱신
            $user->setLastVisit();

            $user = UserServices::getAllProperty($user->userId);
            $ctx->addResBody(['user' => $user->toArray()]);

            $ctx->next();
        }, Auth::sessionGenerator());

        /*************************************************************************************************************
         * hive 정보로 회원가입
         *************************************************************************************************************/
        $router->post('/register', function (Context $ctx) {
            $data = $ctx->getReqBody();

            // 하이브 정보로 유저 검색 후 이미 존재하는지 검사
            $user = UserDAO::getHiveUser($data['hive_id'], $data['hive_uid']);
            CE::check(!$user->isEmpty(), ErrorCode::ALREADY_EXISTS);

            // 없는 정보일 시 새로운 계정 생성
            DB::beginTransaction();
            $userId = UserDAO::createUserPlatform([
                'hiveId' => $data['hive_id'],
                'hiveUid' => $data['hive_uid'],
                'country' => $data['country'],
                'lang' => $data['lang'],
                'osVersion' => $data['os_version'],
                'deviceName' => $data['device_name'],
                'appVersion' => $data['app_version']
            ]);
            UserDAO::createUserInfo($userId);
            UserDAO::createUserStat($userId);
            DB::endTransaction();

            $user = UserServices::getAllProperty($userId);
            $ctx->addResBody(['user' => $user->toArray()]);

            $ctx->next();
        }, Auth::sessionGenerator());

        // 이후로는 세션 검증 처리 필요
        $router->use('/', Auth::sessionHandler());
        /*************************************************************************************************************
         * 이름 변경
         *************************************************************************************************************/
        $router->put('/name', function (Context $ctx) {
            $data = $ctx->getReqBody();

            // 이름이 설정 되지 않은 상태인지 검사
            $user = UserDAO::getUserInfo($data['user_id']);
            CE::check(isset($user->name), ErrorCode::ALREADY_HAS_NAME);

            // 최초 로그인 후 영주 이름 설정
            $user->setName($data['name']);

            $user = UserServices::getAllProperty($user->userId);
            $ctx->addResBody(['user' => $user->toArray()]);
        });

        /*************************************************************************************************************
         * 영토 변경
         *************************************************************************************************************/
        $router->put('/territory', function (Context $ctx) {
            $data = $ctx->getReqBody();

            // 영토가 설정 되지 않은 상태인지 검사
            $user = UserDAO::getUserInfo($data['user_id']);
            CE::check(is_null($user->territoryId), ErrorCode::ALREADY_HAS_TERRITORY);

            // 최초 로그인 시 영토 지정
            $user->setTerritoryId($data['territory_id']);

            $user = UserServices::getAllProperty($user->userId);
            $ctx->addResBody(['user' => $user->toArray()]);
        });

        /*************************************************************************************************************
         * 유저 정보 검색
         *************************************************************************************************************/
        $router->get('/info/:user_id', function (Context $ctx) {
            $data = $ctx->getReqBody();
            $user = UserServices::getAllProperty($data['user_id']);
            $ctx->addResBody(['user' => $user->toArray()]);
        });

        /*************************************************************************************************************
         * 성 업그레이드 요청
         *************************************************************************************************************/
        $router->post(
            '/upgrade',
            // 여러 단말기로 API 여러번 날리는 경우 방지
            // 자원 확인, 소모 사이에 외부에서의 자원량 갱신이 없어야함
            Lock::lockUser(SpinLock::RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getReqBody();

                $user = UserDAO::getUserInfo($data['user_id']);

                // 업그레이드에 필요한 자원
                $level = $user->currentCastleLevel;
                list($tatical, $food, $luxury)
                    = Plan::getBuildingUpgradeResources(Plan::BUILDING_ID_CASTLE, $level);
                list(, $upgradeUnitTime) = Plan::getBuildingUnitTime(Plan::BUILDING_ID_CASTLE, $level);
                list(, $maxLevel) = Plan::getBuildingUpgradeStatus(Plan::BUILDING_ID_CASTLE);

                // 성 업그레이드 가능 여부 검사
                CE::check($level >= $maxLevel, ErrorCode::MAX_LEVEL);
                CE::check($user->isUpgrading(), ErrorCode::IS_UPGRADING);
                CE::check($user->hasResource($tatical, $food, $luxury), ErrorCode::RESOURCE_INSUFFICIENT);

                // 유저 자원 소모, 성 업그레이드
                $user
                    ->useResources($tatical, $food, $luxury, true)
                    ->upgradeCastleLevel($upgradeUnitTime);

                $user = UserServices::getAllProperty($user->userId);
                $ctx->addResBody(['user' => $user->toArray()]);
            }
        );

        /*************************************************************************************************************
         * 성 업그레이드 완료 확인
         *************************************************************************************************************/
        $router->get('/upgrade', function (Context $ctx) {
            $data = $ctx->getReqBody();

            $user = UserDAO::getUserInfo($data['user_id']);

            // 유저 성 업그레이드 완료 여부 검사
            CE::check(false === $user->isUpgraded(), ErrorCode::IS_NOT_UPGRADED);

            $ctx->addResBody(['user' => $user->toArray()]);
        });

        /*************************************************************************************************************
         * 동맹용 덱 등록
         *************************************************************************************************************/
        $router->put('/deck', function (Context $ctx) {
            $data = $ctx->getReqBody();

            $user = UserDAO::container($data['user_id']);
            $user->setFriendArmyDeck();

            $user = UserServices::getAllProperty($user->userId);
            $ctx->addResBody(['user' => $user->toArray()]);
        });

        /*************************************************************************************************************
         * 정산
         *************************************************************************************************************/
        $router->put(
            '/calculation',
            Lock::lockUser(SpinLock::RESOURCE),
            function (Context $ctx) {
                $data = $ctx->getReqBody();

                $user = UserDAO::getUserInfo($data['user_id']);

                // 자원 획득
                // TODO: 정산 자원 계산
                list($tactical, $food, $luxury) = BuildingServices::generateResources($user->userId, $user->lastVisit);
                $user->takeResources($tactical, $food, $luxury);

                $user = UserServices::getAllProperty($user->userId);
                $ctx->addResBody(['user' => $user->toArray()]);
            }
        );
    }
}
