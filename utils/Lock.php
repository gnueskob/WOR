<?php

namespace lsb\Utils;

use lsb\Libs\Context;
use lsb\Libs\SpinLock;
use Exception;

class Lock
{
    public static function lock(string $field, int $expire = 1)
    {
        return function (Context $ctx) use ($field, $expire) {
            $data = $ctx->getBody();
            $userId = $data['user_id'];

            $spinlockKey = SpinLock::getKey($field, $userId);
            SpinLock::spinLock($spinlockKey, $expire);
            try {
                $ctx->next();
                SpinLock::spinUnlock($spinlockKey);
            } catch (Exception $e) {
                SpinLock::spinUnlock($spinlockKey);
                throw $e;
            }
        };
    }
}
