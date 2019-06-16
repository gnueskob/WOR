<?php

namespace lsb\App\services;

use Exception;
use lsb\App\models\BuildingDAO;
use lsb\App\models\Query;
use lsb\App\models\UserDAO;
use lsb\App\models\WeaponDAO;
use lsb\Libs\CtxException;
use PDOStatement;

abstract class Services
{
    /**
     * @var Query $queryContainer
     */
    protected static $queryContainer = null;

    /**
     * @param Query $query
     * @param bool $pending
     * @param array $exceoptions
     * @return int|mixed|PDOStatement|null
     * @throws CtxException
     */
    protected static function validateUpdate(Query $query, bool $pending, array $exceoptions = [])
    {
        if ($pending) {
            if (empty(static::$queryContainer)) {
                static::$queryContainer = $query;
            } else {
                static::$queryContainer->mergeQuery($query);
            }
            return null;
        } else {
            $stmt = $query
                ->checkError($exceoptions)
                ->run(static::$queryContainer);
            static::$queryContainer = null;

            if ($stmt instanceof PDOStatement) {
                CtxException::updateFail($stmt->rowCount() === 0);
                return null;
            }
            return $stmt;
        }
    }

    /**
     * @param PDOStatement|string $stmt
     * @return PDOStatement|string $stmt
     * @throws CtxException
     */
    protected static function validateInsert($stmt)
    {
        if ($stmt instanceof PDOStatement) {
            CtxException::InsertFail($stmt->rowCount() === 0);
            return null;
        }
        return $stmt;
    }

    /**
     * @param PDOStatement|string $stmt
     * @return PDOStatement|string $stmt
     * @throws CtxException
     */
    protected static function validateDelete($stmt)
    {
        CtxException::deleteFail($stmt->rowCount() === 0);
    }

    /**
     * @param PDOStatement $stmt
     * @return UserDAO
     * @throws Exception
     */
    protected static function getUserDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new UserDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return BuildingDAO
     * @throws Exception
     */
    protected static function getBuildingDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new BuildingDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return BuildingDAO[]
     * @throws Exception
     */
    protected static function getBuildingDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new BuildingDAO($row);
        }
        return $res;
    }

    /**
     * @param PDOStatement $stmt
     * @return WeaponDAO
     * @throws Exception
     */
    protected static function getWeaponDAO(PDOStatement $stmt)
    {
        $res = $stmt->fetch();
        $res = $res === false ? [] : $res;
        return new WeaponDAO($res);
    }

    /**
     * @param PDOStatement $stmt
     * @return array
     * @throws Exception
     */
    protected static function getWeaponDAOs(PDOStatement $stmt)
    {
        $res = [];
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $res[] = new WeaponDAO($row);
        }
        return $res;
    }
}
