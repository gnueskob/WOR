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
                if (DB::getTransactionMode() >= 1) {
                    DB::getInstance()->getDBConnection()->rollBack();
                }
                throw $e;
            }
        };
    }
}
