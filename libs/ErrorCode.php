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
    public const NOT_ALLOWED = 5;

    // session
    public const SESSION_INVALID = 100;
    public const SESSION_EXPIRED = 101;

    // create, upgrade error
    public const MAX_LEVEL = 200;
    public const IS_CREATING = 201;
    public const IS_NOT_CREATED = 202;
    public const IS_UPGRADING = 203;
    public const IS_NOT_UPGRADED = 204;
    public const NOT_UPGRADABLE = 205;

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

    // building error
    public const INVALID_BUILDING = 2000;
    public const ALREADY_USED_TILE = 2001;
    public const IS_DEPLOYING = 2002;
    public const IS_NOT_DEPLOYED = 2003;
    public const INSUFFICIENT_MINMANPOWER = 2004;
    public const EXCEED_MAXMANPOWER = 2005;

    // weapon error
    public const INVALID_WEAPON = 3000;
    public const ALREADY_CREATED_WEAPON = 3001;

    // exploration error
    public const INVALID_EXPLORE = 4000;
    public const NOT_USED_LOCATION = 4001;
    public const ALREADY_EXPLORED = 4002;
    public const IS_NOT_EXPLORED = 4003;
    public const IS_NOT_BOSS_TYPE = 4004;

    // buff error
    public const INVALID_BUFF = 5000;
    public const ALREADY_HAS_BUFF = 5001;

    // alliance error
    public const INVALID_ALLIANCE = 6000;
    public const ALREADY_REQUESTED = 6001;
    public const NOT_ALLIANCE = 6002;

    // war error
    public const INVALID_WAR = 7000;
    public const ALREADY_WARRING = 7001;
    public const IS_NOT_FINISHED = 7002;
    public const ALREADY_PREPARED = 7003;

    // raid error
    public const INVALID_RAID = 8000;
    public const ALREADY_RAID = 8001;
    public const TOO_LATE = 8002;
    public const RAID_NOT_FINISHED = 8003;

    // boss error
    public const INVALID_BOSS = 9000;
    public const BOSS_NOT_GEN = 9001;
    public const ALEADY_DIED = 9002;
    public const ALEADY_GEN = 9003;

    // query success but not affected
    public const NO_FETCH = 10000;
    public const NO_UPDATE = 10001;
    public const NO_DELETE = 10002;
    public const NO_INSERT = 10003;
    public const TRANSACTION_FAIL = 10004;
}
