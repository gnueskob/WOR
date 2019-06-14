<?php

namespace lsb\App\services;

use Exception;
use lsb\App\models\UserDAO;
use lsb\Libs\CtxException;
use PDOStatement;

abstract class Services
{
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
     * @throws CtxException
     */
    protected static function validateUpdate(PDOStatement $stmt)
    {
        CtxException::updateFail($stmt->rowCount() === 0);
    }

    /**
     * @param PDOStatement $stmt
     * @throws CtxException
     */
    protected static function validateInsert(PDOStatement $stmt)
    {
        CtxException::InsertFail($stmt->rowCount() === 0);
    }
}
