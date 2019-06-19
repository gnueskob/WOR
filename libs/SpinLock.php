<?php

namespace lsb\Libs;

use Exception;
use PDO;

define('RESOURCE', 'resource');
define('MANPOWER', 'manpower');
define('BOSS', 'boss');

class SpinLock
{
    const RESOURCE = 'resource';
    private static $lockList = [];

    /**
     * @param $key
     * @param $expire
     * @throws Exception
     */
    public static function spinLock($key, $expire)
    {
        static::$lockList[$key] = $expire + microtime(true);

        $db = DB::getInstance()->getDBConnection();
        $db->setAttribute(PDO::ATTR_TIMEOUT, $expire);

        $memcache = Memcached::getInstance()->getMemcached();
        $maxTry = 5;
        $try = 1;
        $delay = 100; //ms

        while (true) {
            if ($try >= $maxTry) {
                throw new Exception("over try count");
            }

            $lock = $memcache->add($key, 0, false, $expire);
            if ($lock) {
                break; // acquire lock success!!
            } else { // acquire lock false :(
                $try++;
                usleep($delay);
            }
        }
    }

    public static function spinUnlock($key)
    {
        $memcache = Memcached::getInstance()->getMemcached();
        $memcache->delete($key);
        $db = DB::getInstance()->getDBConnection();

        if (count(static::$lockList) === 1) {
            $db->setAttribute(PDO::ATTR_TIMEOUT, 10);
        }

        unset(static::$lockList[$key]);
    }

    public static function getKey(string $field, int $id)
    {
        return "{$field}::{$id}";
    }

    public static function getLockList()
    {
        return static::$lockList;
    }
}
