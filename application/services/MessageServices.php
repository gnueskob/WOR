<?php

namespace lsb\App\services;

use lsb\App\models\MessageDAO;
use lsb\App\query\MessageQuery;
use lsb\Libs\CtxException;
use Exception;

class MessageServices
{
    /**
     * @param string $target
     * @param int $userId
     * @param int $targetId
     * @param string $active
     * @return bool
     * @throws CtxException|Exception
     */
    public static function postMessage(string $target, int $userId, int $targetId, string $active)
    {
        CtxException::invalidType(!in_array($target, MessageQuery::$targetList));

        $msg = new MessageDAO();
        $msg->userId = $userId;
        $msg->targetId = $targetId;
        $msg->activeTime = $active;
        $msg->target = $target;

        $stmt = MessageQuery::insertMessage($msg);
        CtxException::insertFail($stmt->rowCount() === 0);

        return true;
    }

    /**
     * @param string $target
     * @param int $userId
     * @param int $targetId
     * @throws CtxException
     */
    public static function removeMessage(string $target, int $userId, int $targetId)
    {
        CtxException::invalidType(!in_array($target, MessageQuery::$targetList));

        $msg = new MessageDAO();
        $msg->userId = $userId;
        $msg->targetId = $targetId;
        $msg->target = $target;

        $stmt = MessageQuery::deleteMessage($msg);
        CtxException::deleteFail($stmt->rowCount() === 0);
    }

    /**
     * @param string $target
     * @param int $userId
     * @throws CtxException
     */
    public static function removeMessageByUser(string $target, int $userId)
    {
        CtxException::invalidType(!in_array($target, MessageQuery::$targetList));

        $msg = new MessageDAO();
        $msg->userId = $userId;
        $msg->target = $target;

        MessageQuery::deleteMessage($msg);
    }
}
