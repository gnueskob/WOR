<?php

namespace lsb\Libs;

class ErrorCode
{
    public const FATAL_ERROR = -1;
    public const FINE = 0;
    public const UNKNOWN_EXCEPTION = 1;
    public const DB_ERROR = 2;
    public const REDIS_ERROR = 3;
    public const MEMCACHED_ERROR = 4;

    // session
    public const SESSION_INVALID = 100;
    public const SESSION_EXPIRED = 101;

    // query success but not affected
    public const NO_FETCH = 10000;
    public const NO_UPDATE = 10001;
    public const NO_DELETE = 10002;
    public const NO_INSERT = 10003;
    public const TRANSACTION_FAIL = 10004;

    // user error
    public const INVALID_USER = 1000;
    public const ALREADY_EXISTS = 1001;
    public const REGISTER_FAIL = 1002;
    public const DUPLICATE_NAME = 1003;
    public const DUPLICATE_TERRITORY = 1004;
    public const RESOURCE_INSUFFICIENT = 1005;
    public const MANPOWER_INSUFFICIENT = 1006;
    public const ALREADY_HAS_NAME = 1007;
    public const ALREADY_HAS_TERRITORY = 1008;

    // upgrade error
    public const MAX_LEVEL = 2000;
    public const IS_UPGRADING = 2001;
    public const IS_NOT_UPGRADED = 2002;
}
