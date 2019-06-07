<?php

namespace lsb\App\models;

use Exception;

class Utils
{
    public static function getUpdateSetClause(... $d)
    {
        $set = '';
        foreach ($d as $key => $value) {
            $set = "{$set}, {$key}=:{$key}";
        }
        return ltrim($set, ', ');
    }

    public static function getParamenters(... $d)
    {
        $param = [];
        foreach ($d as $key => $value) {
            $paramKey = ":{$key}";
            $param[$paramKey] = $value;
        }
        return $param;
    }

    public static function makeSnakeCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public static function toArray(object $class)
    {
        $properties = get_object_vars($class);
        $res = [];
        foreach ($properties as $key => $value) {
            $resKey = self::makeSnakeCase($key);
            $res[$resKey] = $value;
        }
        return $res;
    }

    /**
     * @param object $obj
     * @param array $dbColumMap
     * @return array
     * @throws Exception
     */
    public static function getQueryParameters(object $obj, array $dbColumMap)
    {
        $properties = get_object_vars($obj);
        $res = [];
        foreach ($properties as $key => $value) {
            if (is_null($value)) {
                continue;
            } elseif ($value === 'NULL') {
                $value = null;
            }

            $dbColumn = $dbColumMap[$key];
            if (empty($dbColumn)) {
                throw new Exception("Can not map object property to db column", 500);
            }
            $res[$dbColumn] = $value;
        }
        return $res;
    }
}
