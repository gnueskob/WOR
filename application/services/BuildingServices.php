<?php

namespace lsb\App\services;

use lsb\App\models\BuildingQuery;
use lsb\App\models\UserQuery;
use lsb\Libs\CtxException;
use lsb\Libs\DB;
use Exception;
use PDOException;

class BuildingServices
{
    /**
     * @param array $data
     * @return array
     * @throws CtxException
     */
    public static function getBuildingsByUser(array $data)
    {
        $stmt = BuildingQuery::selectBuildingByUser($data);
        $res = $stmt->fetchAll();
        if ($res === false) {
            (new CtxException())->selectFail('getUserBuilding');
        }
        foreach ($res as $key => $value) {
            $res[$key] = DB::trimColumn($value);
        }
        return $res;
    }

    /**
     * @param array $data
     * @return array
     * @throws CtxException
     */
    public static function getBuilding(array $data)
    {
        $stmt = BuildingQuery::selectBuilding($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail('getBuilding');
        }
        return DB::trimColumn($res);
    }

    /**
     * @param array $data
     * @return array|bool|mixed
     * @throws Exception
     */
    public static function createBuilding(array $data)
    {
        $db = DB::getInstance()->getDBConnection();

        try {
            $db->beginTransaction();

            $stmt = UserQuery::updateUserResource($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('createBuilding');
            }

            $stmt = BuildingQuery::insertBuilding($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('createBuilding');
            }

            $buildingId = $db->lastInsertId();
            $data['building_id'] = $buildingId;
            $stmt = BuildingQuery::insertBuildingCreate($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('createBuilding');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail('createBuilding');
            }

            return $buildingId;
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @throws CtxException
     */
    public static function resolveCreateBuilding(array $data)
    {
        $stmt = BuildingQuery::selectBuilding($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail('addFinishUserBuilding');
        }
        $createFinishTime = $res['create_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            $stmt = BuildingQuery::deleteBuildingCreate($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('addFinishUserBuilding');
            }

            $data['create_time'] = $createFinishTime;
            $stmt = BuildingQuery::updateBuildingWithCreateTime($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('addFinishUserBuilding');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail('upgradeRequestBuilding');
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @throws CtxException
     */
    public static function upgradeBuilding(array $data)
    {
        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            $stmt = UserQuery::updateUserResource($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('upgradeRequestBuilding');
            }

            $stmt = BuildingQuery::insertBuildingUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('upgradeRequestBuilding');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail('upgradeRequestBuilding');
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function resolveUpgradeBuilding(array $data)
    {
        $stmt = BuildingQuery::selectBuilding($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail('upgradeFinishBuilding');
        }
        $toLevel = $res['to_level'];
        $upgradeTime = $res['upgrade_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            $stmt = BuildingQuery::deleteBuildingUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail('upgradeFinishBuilding');
            }

            $data['upgrade'] = $toLevel;
            $data['upgrade_time'] = $upgradeTime;
            $stmt = BuildingQuery::updateBuildingWithUpgrade($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('upgradeFinishBuilding');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail('upgradeRequestBuilding');
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @throws CtxException
     */
    public static function deployBuilding(array $data)
    {
        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            $stmt = UserQuery::updateUserUsedManpower($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('deployBuilding');
            }

            $stmt = BuildingQuery::insertBuildingDeploy($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail('deployBuilding');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail('deployBuilding');
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function resolveDeployBuilding(array $data)
    {
        $stmt = BuildingQuery::selectBuilding($data);
        $res = $stmt->fetch();
        if ($res === false) {
            (new CtxException())->selectFail('resolveDeployBuilding');
        }
        $toLevel = $res['deploy_finish_time'];

        $db = DB::getInstance()->getDBConnection();
        try {
            $db->beginTransaction();

            $stmt = BuildingQuery::deleteBuildingDeploy($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->deleteFail('resolveDeployBuilding');
            }

            $data['upgrade'] = $toLevel;
            $stmt = BuildingQuery::updateBuildingWithDeployTime($data);
            if ($stmt->rowCount() === 0) {
                (new CtxException())->updateFail('resolveDeployBuilding');
            }

            if ($db->commit() === false) {
                (new CtxException())->transactionFail('resolveDeployBuilding');
            }
        } catch (CtxException | PDOException | Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
