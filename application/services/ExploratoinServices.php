<?php

namespace lsb\App\services;

use lsb\App\models\UserQuery;
use lsb\App\models\ExplorationQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use PDOException;

class ExploratoinServices
{
    /**
     * @param array $data
     * @return array|bool
     * @throws CtxException
     */
    public static function getTile(array $data)
    {
        $stmt = ExplorationQuery::selectTile($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        return $res;
    }

    /**
     * @param array $data
     * @return array|bool
     * @throws CtxException
     */
    public static function getTilesByUser(array $data)
    {
        $stmt = ExplorationQuery::selectTilesByUser($data);
        $res = $stmt->fetchAll();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        foreach ($res as $key => $value) {
            $res[$key] = DB::trimColumn($value);
        }
        return $res;
    }

    /**
     * @param array $data
     * @return mixed
     * @throws CtxException
     */
    public static function getTerritory(array $data)
    {
        $stmt = ExplorationQuery::selectTerritory($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        return $res;
    }

    /**
     * @param array $data
     * @return array|bool
     * @throws CtxException
     */
    public static function getTerritoriesByUser(array $data)
    {
        $stmt = ExplorationQuery::selectTerritoriesByUser($data);
        $res = $stmt->fetchAll();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        foreach ($res as $key => $value) {
            $res[$key] = DB::trimColumn($value);
        }
        return $res;
    }

    /**
     * @param array $data
     * @return string
     * @throws CtxException
     */
    public static function exploreTile(array $data)
    {
        $db = DB::getInstance()->getDBConnection();

        try {
            $db->beginTransaction();

            // 타일 탐사 데이터 추가
            $stmt = ExplorationQuery::insertTile($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            // 타일 탐사 job 추가
            $exploreId = $db->lastInsertId();
            $data['explore_id'] = $exploreId;
            $stmt = ExplorationQuery::insertTileExplore($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }

            return $exploreId;
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @throws CtxException
     */
    public static function resolveExploreTile(array $data)
    {
        // 기존 타일 탐사 정보 검색
        $stmt = ExplorationQuery::selectTile($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        $exploreTime = $res['explore_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 탐사 job 삭제
            $stmt = ExplorationQuery::deleteTimeExplore($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 업그레이드 레벨 갱신
            $data['explore_time'] = $exploreTime;
            $stmt = ExplorationQuery::updateTileWithExploreTime($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @return string
     * @throws CtxException
     */
    public static function exploreTerritory(array $data)
    {
        $db = DB::getInstance()->getDBConnection();

        try {
            $db->beginTransaction();

            // 유저 인력 갱신
            $stmt = UserQuery::updateUserInfoWithUsedManpower($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            // 영토 탐사 데이터 추가
            $stmt = ExplorationQuery::insertTerritory($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            // 영토 탐사 job 추가
            $exploreId = $db->lastInsertId();
            $data['explore_id'] = $exploreId;
            $stmt = ExplorationQuery::insertTerritoryExplore($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }

            return $exploreId;
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @throws CtxException
     */
    public static function resolveExploreTerritory(array $data)
    {
        // 기존 영토 탐사 정보 검색
        $stmt = ExplorationQuery::selectTerritory($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail();
        }
        $exploreTime = $res['explore_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            // 탐사 job 삭제
            $stmt = ExplorationQuery::deleteTerritoryExplore($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail();
            }

            // 탐사 시간 갱신
            $data['explore_time'] = $exploreTime;
            $stmt = ExplorationQuery::updateTerritoryWithExploreTime($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail();
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail();
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

}
