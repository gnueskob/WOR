<?php

namespace lsb\App\models;

use Exception;
use lsb\Libs\CtxException;

class Utils
{
    /*
    public static function getUpdateSetClause(DAO $dao, array $d)
    {
        $map = $dao->getPropertyToDBColumnMap();
        $d = $dao->getPropertyToQuery();
        $set = '';
        foreach ($d as $key) {
            $column = $map[$key];
            if (is_null($column)) {
                throw new Exception('No property exists in column map', 500);
            }
            $set = "{$set}, {$map[$key]}=:{$map[$key]}";
        }
        return ltrim($set, ', ');
    }

    public static function getBindParamenters(DAO $dao)
    {
        $map = $dao->getPropertyToDBColumnMap();
        $d = $dao->getPropertyToQuery();
        $param = [];
        foreach ($d as $key) {
            $column = $map[$key];
            if (is_null($column)) {
                throw new Exception('No property exists in column map', 500);
            }
            $paramKey = ":{$map[$key]}";
            $param[$paramKey] = $dao->{$key};
        }
        return $param;
    }*/

    public static function makeSnakeCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    private static function makePropertySnakeCase(DAO $dao)
    {
        $properties = get_object_vars($dao);
        $res = [];
        foreach ($properties as $key => $value) {
            $resKey = self::makeSnakeCase($key);
            $res[$resKey] = $value;
        }
        return $res;
    }

    /**
     * @param DAO $dao
     * @return array
     * @throws Exception
     */
    public static function toArray(DAO $dao)
    {
        return self::makePropertySnakeCase($dao);
    }

    public static function toArrayAll(array $daos)
    {
        $res = [];
        foreach ($daos as $dao) {
            $res[] = self::makePropertySnakeCase($dao);
        }
        return $res;
    }

    /**
     * @param DAO $dao
     * @throws CtxException
     */
    public static function checkNull(DAO $dao)
    {
        if (is_null($dao)) {
            (new CtxException())->selectFail();
        }
    }

    /*
    public static function getQueryParameters(DAO $obj)
    {
        $map = $obj->getPropertyToDBColumnMap();
        $properties = $obj->getPropertyToQuery();
        $res = [];
        foreach ($properties as $property) {
            $dbColumn = $map[$property];
            if (is_null($dbColumn)) {
                throw new Exception("Can not map DAO object property to db column", 500);
            }
            $res[$dbColumn] = $obj->{$property};
        }
        return $res;
    }*/
}
