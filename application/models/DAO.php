<?php

namespace lsb\App\models;

use Exception;

abstract class DAO
{
    private $updatedProperty = [];

    abstract public function getDBColumnToPropertyMap();
    abstract public function getPropertyToDBColumnMap();

    /**
     * DAO constructor.
     * @param array $data
     * @param array $dbColumToPropertyMap
     * @throws Exception
     */
    public function __construct(array $data, array $dbColumToPropertyMap)
    {

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

    public function updateProperty(array $p)
    {
        foreach ($p as $property => $value) {
            $this->{$property} = $value;
            $this->updatedProperty[] = $property;
        }
    }

    public function getUpdateValue()
    {
        return $this->updatedProperty;
    }
}