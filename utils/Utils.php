<?php

namespace lsb\Utils;

use Exception;
use lsb\App\models\DAO;
use lsb\Config\Config;
use lsb\Libs\Context;
use lsb\Libs\CtxException;

class Utils
{
    public static function makeSnakeCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * @param DAO[] $daos
     * @return array
     * @throws CtxException
     */
    public static function toArrayAll(array $daos)
    {
        $res = [];
        foreach ($daos as $dao) {
            $res[] = $dao->toArray();
        }
        return $res;
    }

    public static function getObjectVars(object $obj)
    {
        return get_object_vars($obj);
    }


    public static function getDistance(int $x1, int $y1, int $x2, int $y2)
    {
        // Manhattan Distance
        return abs($x1 - $x2) + abs($y1 - $y2);
    }
}
