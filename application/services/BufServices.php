<?php

namespace lsb\App\services;

use lsb\App\models\BufDAO;
use lsb\App\query\BufQuery;
use lsb\Libs\CtxException;
use lsb\Libs\Timezone;
use lsb\Libs\DB;
use Exception;
use PDOException;

class BufServices
{
    /**
     * @param int $bufId
     * @return BufDAO
     * @throws CtxException|Exception
     */
    public static function getBuf(int $bufId)
    {
        $container = new BufDAO();
        $container->bufId = $bufId;

        $stmt = BufQuery::selectBufByUser($container);
        $res = $stmt->fetch();
        if ($res === false) {
            return null;
        }
        return new BufDAO($res);
    }

    /**
     * @param int $userId
     * @return array
     * @throws Exception|Exception
     */
    public static function getBufsByUser(int $userId)
    {
        $container = new BufDAO();
        $container->userId = $userId;

        $stmt = BufQuery::selectBufByUser($container);
        $res = $stmt->fetchAll();
        if ($res === false) {
            return [];
        }
        foreach ($res as $key => $value) {
            $res[$key] = new BufDAO($value);
        }
        return $res;
    }

    /**
     * @param int $userId
     * @param int $bufType
     * @param string $date
     * @return string
     * @throws CtxException|Exception
     */
    public static function makeBuf(int $userId, int $bufType, string $date)
    {
        $container = new BufDAO();
        $container->userId = $userId;
        $container->bufType = $bufType;
        $container->finishTime = $date;

        try {
            $stmt = BufQuery::insertBuf($container);

            if ($stmt->rowCount() === 0) {
                (new CtxException())->insertFail();
            }

            $db = DB::getInstance()->getDBConnection();
            return $db->lastInsertId();
        } catch (PDOException $e) {
            // Unique key 중복 제한으로 걸릴 경우 따로 처리
            if ($e->getCode() === DUPLICATE_ERRORCODE) {
                (new CtxException())->alreadyExistsBuf();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param int $userId
     * @throws Exception
     */
    public static function refreshBuf(int $userId)
    {
        $container = new BufDAO();
        $container->userId = $userId;

        BufQuery::deleteBufByUser($container);
    }
}
