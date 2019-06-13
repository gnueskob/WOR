<?php

namespace lsb\App\services;

use lsb\Libs\CtxException;
use lsb\App\models\DAO;
use PDOStatement;
use PDOException;

abstract class Update
{
    private static $container = null;

    abstract protected static function getNewContainer();
    abstract protected static function updateAll(DAO $container, bool $assign): PDOStatement;

    protected static function getContainer()
    {
        if (is_null(self::$container)) {
            self::$container = self::getNewContainer();
        }
        return self::$container;
    }

    /**
     * @param bool $assign
     * @return bool
     * @throws CtxException
     */
    public static function apply(bool $assign = false)
    {
        $container = self::getContainer();

        try {
            $stmt = self::updateAll($container, $assign);
            self::$container = self::getNewContainer();
            CtxException::updateFail($stmt->rowCount() === 0);
            return true;
        } catch (PDOException $e) {
            // Unique key 중복 제한으로 걸릴 경우 따로 처리
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                return false;
            } else {
                throw $e;
            }
        }
    }
}
