<?php

namespace lsb\App\services;

use Exception;
use lsb\App\models\BuildingDAO;
use lsb\App\query\Query;
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
}
