<?php

namespace lsb\App\models;

use Exception;
use lsb\App\query\Query;
use lsb\Libs\CtxException;
use lsb\Libs\CtxException as CE;
use lsb\Libs\ErrorCode;
use lsb\Utils\Utils;
use PDOStatement;

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
        CE::check($this->isEmpty(), ErrorCode::NO_FETCH);

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

    /******************************************************/

    /* @var Query */
    private $query;

    /**
     * @param Query $query
     * @param bool $pending
     * @param array $exceoptions
     * @return int|mixed|PDOStatement
     * @throws CE
     */
    public function resolveUpdate(Query $query, bool $pending, array $exceoptions = [])
    {
        if ($pending) {
            if (is_null($this->query)) {
                $this->query = $query;
            } else {
                $this->query->mergeQuery($query);
            }
        } else {
            $stmt = $query
                ->checkError($exceoptions)
                ->run($this->query);

            if ($stmt instanceof PDOStatement) {
                CE::check($stmt->rowCount() === 0, ErrorCode::NO_UPDATE);
            } else {
                $errorCode = $stmt;
                return $errorCode;
            }
        }
    }

    /**
     * @param $stmt
     * @return int
     * @throws CE
     */
    protected static function resolveInsert($stmt)
    {
        if ($stmt instanceof PDOStatement) {
            CE::check($stmt->rowCount() === 0, ErrorCode::NO_INSERT);
            return 0;
        } else {
            $errorCode = $stmt;
            return $errorCode;
        }
    }

    /**
     * @param PDOStatement|string $stmt
     * @return PDOStatement|string $stmt
     * @throws CtxException
     */
    protected function resolveDelete($stmt)
    {
        CE::check($stmt->rowCount() === 0, ErrorCode::NO_DELETE);
    }
}
