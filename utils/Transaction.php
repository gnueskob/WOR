<?php

namespace lsb\Config\utils;

use lsb\Libs\Context;
use lsb\Libs\DB;
use Exception;

class Transaction
{
    public static function transactionHandler()
    {
        return function (Context $ctx) {
            try {
                $ctx->next();
            } catch (Exception $e) {
                $isTransactionMode = DB::getTransactionMode();
                if ($isTransactionMode) {
                    DB::getInstance()->getDBConnection()->rollBack();
                }
                throw $e;
            }
        };
    }
}
