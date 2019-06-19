<?php

namespace lsb\App\models;

use Exception;
use lsb\Libs\CtxException;
use lsb\Utils\Utils;

abstract class DAO
{
    private $empty = false;
    private $updatedProperty = [];

    /**
     * DAO constructor.
     * @param array $data
     * @param array $dbColumToPropertyMap
     * @throws Exception
     */
    public function __construct(array $data = [], array $dbColumToPropertyMap = [])
    {
        if (count($data) === 0) {
            $this->empty = true;
            return;
        }

        foreach ($data as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $property = $dbColumToPropertyMap[$key];
            if (is_null($property)) {
                throw new Exception("Not registered db column name", 500);
            }
            $this->{$property} = $value;
        }
    }

    /**
     * @param $name
     * @param $value
     * @throws Exception
     */
    public function __set($name, $value)
    {
        throw new Exception("No property exists in DAO", 500);
    }

    /**
     * @param $name
     * @throws Exception
     */
    public function __get($name)
    {
        throw new Exception("No property exists in DAO", 500);
    }

    /**
     * @return array
     * @throws CtxException
     */
    public function toArray()
    {
        CtxException::selectFail($this->isEmpty());

        $properties = Utils::getObjectVars($this);
        $res = [];
        foreach ($properties as $key => $value) {
            $resKey = Utils::makeSnakeCase($key);
            $res[$resKey] = $value;
        }
        return $res;
    }

    public function isEmpty()
    {
        return $this->empty;
    }

    public function updateProperty(array $p)
    {
        foreach ($p as $property => $value) {
            $this->updatedProperty[] = $property;
        }
    }

    public function getPropertyToQuery()
    {
        return $this->updatedProperty;
    }
}
